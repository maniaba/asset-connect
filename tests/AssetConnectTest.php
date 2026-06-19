<?php

declare(strict_types=1);

namespace Tests;

use CodeIgniter\Config\Factories;
use CodeIgniter\Entity\Entity;
use CodeIgniter\Events\Events;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\ReflectionHelper;
use Maniaba\AssetConnect\Asset\Asset;
use Maniaba\AssetConnect\AssetCollection\SetupAssetCollection;
use Maniaba\AssetConnect\AssetConnect;
use Maniaba\AssetConnect\Config\Asset as AssetConfig;
use Maniaba\AssetConnect\Events\AssetDeleted;
use Maniaba\AssetConnect\Exceptions\AssetException;
use Maniaba\AssetConnect\Exceptions\InvalidArgumentException;
use ReflectionClass;
use RuntimeException;
use Serializable;
use Tests\Support\AssetCollections\FakeAvatarCollection;
use Tests\Support\AssetCollections\FakeDocumentCollection;
use Tests\Support\Config\TestAssetConfig;
use Tests\Support\Models\AssetConnectTestAssetModel;
use Tests\Support\TestEntity;

/**
 * @internal
 */
final class AssetConnectTest extends CIUnitTestCase
{
    use ReflectionHelper;

    protected function setUp(): void
    {
        parent::setUp();

        AssetConnectTestAssetModel::resetTestState();

        $config             = new TestAssetConfig();
        $config->assetModel = AssetConnectTestAssetModel::class;

        Factories::injectMock('config', AssetConfig::class, $config);
        Factories::injectMock('config', 'Asset', $config);
    }

    public function testImplementsSerializable(): void
    {
        $reflection = new ReflectionClass(AssetConnect::class);

        $this->assertTrue($reflection->implementsInterface(Serializable::class));
    }

    public function testSerializeRestoresCachedAssetsAndRuntimeDependencies(): void
    {
        $assetConnect = new AssetConnect();
        $asset        = $this->newAsset(456);
        $entity       = new TestEntity();

        $assetConnect->addAsset($asset);
        $this->setPrivateProperty($assetConnect, 'fetchedCollections', [null]);

        $restored = unserialize(serialize($assetConnect));

        $this->assertInstanceOf(AssetConnect::class, $restored, 'Native unserialize should restore an AssetConnect instance.');
        $this->assertNotSame($assetConnect->assetModel, $restored->assetModel, 'Runtime model dependency should be rebuilt after native unserialize.');
        $this->assertInstanceOf(
            SetupAssetCollection::class,
            $this->getPrivateProperty($restored, 'setupAssetCollection'),
            'SetupAssetCollection dependency should be rebuilt after native unserialize.',
        );

        $assets = $restored->getAssetsForEntity($entity);

        $this->assertCount(1, $assets, 'Cached assets should remain available after native serialization round trip.');
        $this->assertSame($asset->id, $assets[$asset->id]->id, 'Restored cached asset should keep its original id.');
        $this->assertSame($asset->collection, $assets[$asset->id]->collection, 'Restored cached asset should keep its collection.');
    }

    public function testSerializableMethodsRoundTripCachedAssets(): void
    {
        $assetConnect = new AssetConnect();
        $asset        = $this->newAsset(789);
        $entity       = new TestEntity();

        $assetConnect->addAsset($asset);
        $this->setPrivateProperty($assetConnect, 'fetchedCollections', [null]);

        /** @var AssetConnect $restored */
        $restored = (new ReflectionClass(AssetConnect::class))->newInstanceWithoutConstructor();
        $restored->unserialize($assetConnect->serialize());

        $assets = $restored->getAssetsForEntity($entity);

        $this->assertCount(1, $assets, 'Serializable::unserialize should restore cached assets from Serializable::serialize payload.');
        $this->assertSame($asset->id, $assets[$asset->id]->id, 'Serializable round trip should keep the cached asset id.');
    }

    public function testUnserializeRejectsInvalidPayload(): void
    {
        $assetConnect = new AssetConnect();

        try {
            $assetConnect->unserialize(serialize('invalid'));
            $this->fail('Invalid serialized payload should throw an InvalidArgumentException.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Invalid argument provided', $exception->getMessage(), 'Invalid payload should use the package invalid argument message.');
            $this->assertSame(['Invalid serialized AssetConnect data.'], $exception->errors, 'Invalid payload should keep the specific serialization error in exception errors.');
        }
    }

    public function testTriggerModelAfterFindReturnsOriginalDataForEmptyRows(): void
    {
        $assetConnect = new AssetConnect();
        $data         = [
            'singleton' => false,
            'data'      => [],
        ];

        $this->assertSame($data, $assetConnect->triggerModelAfterFind($data), 'Empty afterFind rows should be returned unchanged.');
    }

    public function testTriggerModelAfterFindSkipsRowsThatAreNotEntities(): void
    {
        $assetConnect = new AssetConnect();
        $data         = [
            'singleton' => false,
            'data'      => [
                ['id' => 123],
                (object) ['id' => 456],
            ],
        ];

        $this->assertSame($data, $assetConnect->triggerModelAfterFind($data), 'Non-entity afterFind rows should be skipped unchanged.');

        $relationsInfo = $this->getPrivateProperty($assetConnect, 'relationsInfo');

        $this->assertSame([], $relationsInfo['primaryKeys'], 'Skipped rows should not add primary keys to the relation cache.');
    }

    public function testTriggerModelAfterFindLoadsAssetConnectIntoEntities(): void
    {
        $assetConnect = new AssetConnect();
        $entity       = new TestEntity();
        $data         = [
            'singleton' => true,
            'data'      => $entity,
        ];

        $this->assertSame($data, $assetConnect->triggerModelAfterFind($data), 'Entity afterFind rows should be returned unchanged.');
        $this->assertSame($assetConnect, $entity->assetConnectInstance(), 'Entity should receive the AssetConnect instance during afterFind.');

        $relationsInfo = $this->getPrivateProperty($assetConnect, 'relationsInfo');

        $this->assertSame([$entity->id], $relationsInfo['primaryKeys'], 'Entity primary key should be collected during afterFind.');
    }

    public function testTriggerModelAfterFindRejectsEntityWithoutAssetConnectTrait(): void
    {
        $assetConnect = new AssetConnect();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The entity(Model::$returnType) must use the UseAssetConnectTrait trait.');

        $assetConnect->triggerModelAfterFind([
            'singleton' => true,
            'data'      => new Entity(['id' => 123]),
        ]);
    }

    public function testGetAssetsForEntityReturnsEmptyWhenEntityHasNoPrimaryKey(): void
    {
        $assetConnect = new AssetConnect();

        $this->assertSame([], $assetConnect->getAssetsForEntity(new Entity()), 'Entities without primary key should not query assets.');
        $this->assertSame([], AssetConnectTestAssetModel::$calls, 'No model calls should be made when entity primary key is missing.');
    }

    public function testGetAssetsForEntityFetchesAssetsOncePerCollectionAndFiltersResults(): void
    {
        $documentAsset = $this->newAsset(91, collection: 'fake_documents');
        $avatarAsset   = $this->newAsset(92, collection: 'fake_avatars');
        $assetConnect  = new AssetConnect();
        $entity        = new TestEntity();

        AssetConnectTestAssetModel::$findAllReturn = [$documentAsset, $avatarAsset];

        $assets = $assetConnect->getAssetsForEntity($entity, FakeDocumentCollection::class);

        $this->assertSame([$documentAsset->id => $documentAsset], $assets, 'Requested collection should return only matching fetched assets.');
        $this->assertSame(
            ['groupStart', 'when', 'where', 'whereIn', 'groupEnd', 'orderBy', 'findAll'],
            array_column(AssetConnectTestAssetModel::$calls, 'method'),
            'First collection fetch should build and execute the model query.',
        );

        AssetConnectTestAssetModel::$calls = [];

        $this->assertSame($assets, $assetConnect->getAssetsForEntity($entity, FakeDocumentCollection::class), 'Second call for same collection should use cached assets.');
        $this->assertSame([], AssetConnectTestAssetModel::$calls, 'Second call for same collection should not query the model again.');
    }

    public function testRemoveAssetByIdRemovesAssetFromCachedEntities(): void
    {
        $assetConnect = new AssetConnect();
        $firstAsset   = $this->newAsset(201);
        $secondAsset  = $this->newAsset(202, entityId: 456);

        $assetConnect->addAsset($firstAsset);
        $assetConnect->addAsset($secondAsset);
        $assetConnect->removeAssetById($firstAsset->id);

        $cachedAssets = $this->getPrivateProperty($assetConnect, 'assets');

        $this->assertArrayNotHasKey($firstAsset->id, $cachedAssets[$firstAsset->entity_id], 'Removed asset id should no longer exist in its entity cache.');
        $this->assertArrayHasKey($secondAsset->id, $cachedAssets[$secondAsset->entity_id], 'Other cached assets should not be removed.');
    }

    public function testDeleteAssetsForEntityReturnsTrueWhenEntityHasNoPrimaryKey(): void
    {
        $assetConnect = new AssetConnect();

        $this->assertTrue($assetConnect->deleteAssetsForEntity(new Entity()), 'Deleting assets for an unsaved entity should be a no-op success.');
        $this->assertSame([], AssetConnectTestAssetModel::$calls, 'No model calls should be made when deleting assets for an unsaved entity.');
    }

    public function testDeleteAssetsForEntityThrowsDatabaseErrors(): void
    {
        AssetConnectTestAssetModel::$deleteReturn = false;
        AssetConnectTestAssetModel::$errorsReturn = [
            'database' => 'Delete failed.',
        ];

        $assetConnect = new AssetConnect();

        $this->expectException(AssetException::class);
        $this->expectExceptionMessage('Delete failed.');

        $assetConnect->deleteAssetsForEntity(new TestEntity());
    }

    public function testDeleteAssetsForEntityTriggersEventsAndClearsEntityCache(): void
    {
        $asset        = $this->newAsset(321);
        $assetConnect = new AssetConnect();
        $deletedIds   = [];
        $listener     = static function (AssetDeleted $event) use (&$deletedIds): void {
            $deletedIds[] = $event->getAsset()->id;
        };

        AssetConnectTestAssetModel::$findAllReturn = [$asset];
        $assetConnect->addAsset($asset);
        Events::on(AssetDeleted::name(), $listener);

        try {
            $this->assertTrue($assetConnect->deleteAssetsForEntity(new TestEntity()), 'Deleting cached entity assets should return true when model delete succeeds.');
        } finally {
            Events::removeListener(AssetDeleted::name(), $listener);
        }

        $this->assertSame([$asset->id], $deletedIds, 'AssetDeleted event should be triggered for assets loaded before delete.');
        $this->assertSame([], $this->getPrivateProperty($assetConnect, 'assets'), 'Deleting without collection should clear the entity asset cache.');
    }

    public function testDeleteAssetsForEntityClearsOnlyRequestedCollectionFromCache(): void
    {
        $documentAsset = $this->newAsset(11, collection: 'fake_documents');
        $avatarAsset   = $this->newAsset(12, collection: 'fake_avatars');
        $assetConnect  = new AssetConnect();

        $assetConnect->addAsset($documentAsset);
        $assetConnect->addAsset($avatarAsset);

        $this->assertTrue(
            $assetConnect->deleteAssetsForEntity(new TestEntity(), FakeAvatarCollection::class),
            'Deleting one collection should return true when model delete succeeds.',
        );

        $cachedAssets = $this->getPrivateProperty($assetConnect, 'assets');

        $this->assertArrayHasKey($documentAsset->id, $cachedAssets[$documentAsset->entity_id], 'Unrequested collection asset should remain cached.');
        $this->assertArrayNotHasKey($avatarAsset->id, $cachedAssets[$avatarAsset->entity_id], 'Requested collection asset should be removed from cache.');
    }

    public function testGetAssetByIdReturnsModelAsset(): void
    {
        $asset = $this->newAsset(77);

        AssetConnectTestAssetModel::$findReturn = $asset;

        $this->assertSame($asset, (new AssetConnect())->getAssetById($asset->id), 'getAssetById should return the model asset when it exists.');
    }

    public function testGetAssetByIdThrowsWhenAssetDoesNotExist(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(404);
        $this->expectExceptionMessage('Asset not found');

        (new AssetConnect())->getAssetById(404);
    }

    private function newAsset(int $id, int|string $entityId = 123, string $collection = 'test_collection'): Asset
    {
        $asset = new Asset();

        $asset->id          = $id;
        $asset->entity_id   = $entityId;
        $asset->entity_type = 'test_entity';
        $asset->collection  = $collection;
        $asset->storage     = 'public';
        $asset->path        = 'test-assets/file-' . $id . '.txt';
        $asset->order       = 1;

        return $asset;
    }
}
