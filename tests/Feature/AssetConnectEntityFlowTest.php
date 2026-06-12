<?php

declare(strict_types=1);

namespace Tests\Feature;

use Maniaba\AssetConnect\Asset\Asset;
use Maniaba\AssetConnect\Models\AssetModel;
use Tests\Support\AssetCollections\FakeAvatarCollection;
use Tests\Support\AssetCollections\FakeDocumentCollection;
use Tests\Support\AssetConnectFeatureTestCase;
use Tests\Support\Models\FakeAssetEntityModel;

/**
 * @internal
 */
final class AssetConnectEntityFlowTest extends AssetConnectFeatureTestCase
{
    public function testEntityCanAddFileAndRetrieveItThroughTraitMethods(): void
    {
        $entity = $this->createFakeEntity();
        $source = $this->createSourceFile('first document.txt', 'document contents');

        $asset = $entity->addAsset($source)
            ->usingFileName('first document.txt')
            ->usingName('First Document')
            ->setOrder(10)
            ->withCustomProperties([
                'category' => 'manual',
                'tags'     => ['docs', 'public'],
            ])
            ->preservingOriginal()
            ->toAssetCollection(FakeDocumentCollection::class);

        $this->assertAssetWasStoredForEntity($asset, $entity, 'fake_documents');
        $this->assertAssetFileContains($asset, 'document contents');
        $this->assertFileExists($source);
        $this->assertSame('first-document.txt', $asset->file_name);
        $this->assertSame('First Document', $asset->name);
        $this->assertSame(10, $asset->order);
        $this->assertSame('manual', $asset->getCustomProperty('category'));
        $this->assertSame(['docs', 'public'], $asset->getCustomProperty('tags'));
        $this->assertStringStartsWith('fake-assets/documents/', $asset->path);

        $assets = $this->assertEntityAssetCount($entity, 1);

        $this->assertSame($asset->id, $assets[0]->id);
        $this->assertSame($asset->id, $entity->getFirstAsset()?->id);
        $this->assertSame($asset->id, $entity->getLastAsset()?->id);
    }

    public function testEntityCanMapAssetsToFileNamesAndCustomProperties(): void
    {
        $entity = $this->createFakeEntity();

        $entity->addAsset($this->createSourceFile('alpha.txt', 'alpha'))
            ->usingFileName('alpha.txt')
            ->withCustomProperty('label', 'Alpha')
            ->preservingOriginal()
            ->toAssetCollection(FakeDocumentCollection::class);

        $entity->addAsset($this->createSourceFile('beta.txt', 'beta'))
            ->usingFileName('beta.txt')
            ->withCustomProperty('label', 'Beta')
            ->preservingOriginal()
            ->toAssetCollection(FakeDocumentCollection::class);

        $assets = $this->assertEntityAssetCount($entity, 2, FakeDocumentCollection::class);

        $this->assertSame(['alpha.txt', 'beta.txt'], array_map(static fn (Asset $asset): string => $asset->file_name, $assets));
        $this->assertSame(['Alpha', 'Beta'], array_map(static fn (Asset $asset): mixed => $asset->getCustomProperty('label'), $assets));
    }

    public function testEntityCanDeleteAssetsFromSpecificCollection(): void
    {
        $entity = $this->createFakeEntity();

        $document = $entity->addAsset($this->createSourceFile('document.txt', 'document'))
            ->usingFileName('document.txt')
            ->preservingOriginal()
            ->toAssetCollection(FakeDocumentCollection::class);

        $avatar = $entity->addAsset($this->createSourceFile('avatar.txt', 'avatar'))
            ->usingFileName('avatar.txt')
            ->preservingOriginal()
            ->toAssetCollection(FakeAvatarCollection::class);

        $this->assertEntityAssetCount($entity, 2);

        $this->assertTrue($entity->deleteAssets(FakeAvatarCollection::class));

        $remainingAssets = $this->assertEntityAssetCount($entity, 1);

        $this->assertSame($document->id, $remainingAssets[0]->id);
        $this->assertSame([], $entity->getAssets(FakeAvatarCollection::class));
        $this->assertAssetWasSoftDeleted($avatar);
    }

    public function testEntityCanAddAssetFromString(): void
    {
        $entity = $this->createFakeEntity();

        $asset = $entity->addAssetFromString('inline text', 'inline note.txt')
            ->preservingOriginal()
            ->toAssetCollection(FakeDocumentCollection::class);

        $this->assertAssetWasStoredForEntity($asset, $entity, 'fake_documents');
        $this->assertAssetFileContains($asset, 'inline text');
        $this->assertSame('inline-note.txt', $asset->file_name);
    }

    public function testModelCanFilterAssetsLoadedForFoundEntities(): void
    {
        $entity = $this->createFakeEntity();

        $entity->addAsset($this->createSourceFile('document.txt', 'document'))
            ->usingFileName('document.txt')
            ->preservingOriginal()
            ->toAssetCollection(FakeDocumentCollection::class);

        $avatar = $entity->addAsset($this->createSourceFile('avatar.txt', 'avatar'))
            ->usingFileName('avatar.txt')
            ->preservingOriginal()
            ->toAssetCollection(FakeAvatarCollection::class);

        $model = new FakeAssetEntityModel($this->db);

        $entities = $model
            ->filterAssets(static function (AssetModel $model): void {
                $model->whereCollection(FakeAvatarCollection::class);
            })
            ->findAll();

        $this->assertCount(1, $entities);

        $assets = $this->assertEntityAssetCount($entities[0], 1);

        $this->assertSame($avatar->id, $assets[0]->id);
        $this->assertSame('avatar.txt', $assets[0]->file_name);
    }
}
