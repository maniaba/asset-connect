<?php

declare(strict_types=1);

namespace Tests;

use CodeIgniter\Config\Factories;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\ReflectionHelper;
use Maniaba\AssetConnect\Asset\Asset;
use Maniaba\AssetConnect\AssetCollection\SetupAssetCollection;
use Maniaba\AssetConnect\AssetConnect;
use ReflectionClass;
use Serializable;
use Tests\Support\Config\TestAssetConfig;
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

        Factories::injectMock('config', \Maniaba\AssetConnect\Config\Asset::class, new TestAssetConfig());
    }

    public function testImplementsSerializable(): void
    {
        $reflection = new ReflectionClass(AssetConnect::class);

        $this->assertTrue($reflection->implementsInterface(Serializable::class));
    }

    public function testSerializeRestoresCachedAssetsAndRuntimeDependencies(): void
    {
        $assetConnect = new AssetConnect();
        $asset        = new Asset();
        $entity       = new TestEntity();

        $asset->id          = 456;
        $asset->entity_id   = $entity->id;
        $asset->entity_type = 'test_entity';
        $asset->collection  = 'test_collection';
        $asset->order       = 1;

        $assetConnect->addAsset($asset);
        $this->setPrivateProperty($assetConnect, 'fetchedCollections', [null]);

        $restored = unserialize(serialize($assetConnect));

        $this->assertInstanceOf(AssetConnect::class, $restored);
        $this->assertNotSame($assetConnect->assetModel, $restored->assetModel);
        $this->assertInstanceOf(
            SetupAssetCollection::class,
            $this->getPrivateProperty($restored, 'setupAssetCollection'),
        );

        $assets = $restored->getAssetsForEntity($entity);

        $this->assertCount(1, $assets);
        $this->assertSame($asset->id, $assets[$asset->id]->id);
        $this->assertSame($asset->collection, $assets[$asset->id]->collection);
    }
}
