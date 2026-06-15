<?php

declare(strict_types=1);

namespace Tests\Feature;

use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\SiteURI;
use Config\App;
use Config\Services;
use Maniaba\AssetConnect\Asset\Asset;
use Maniaba\AssetConnect\Asset\AssetAdder;
use Maniaba\AssetConnect\Asset\AssetPersistenceManager;
use Maniaba\AssetConnect\AssetConnect;
use Maniaba\AssetConnect\Enums\AssetVisibility;
use Maniaba\AssetConnect\Exceptions\FileException;
use Maniaba\AssetConnect\Models\AssetModel;
use Maniaba\AssetConnect\Pending\PendingAsset;
use Maniaba\AssetConnect\Storage\Interfaces\StorageDiskInterface;
use PHPUnit\Framework\MockObject\Stub;
use RuntimeException;
use Tests\Support\AssetCollections\FakeAvatarCollection;
use Tests\Support\AssetCollections\FakeDocumentCollection;
use Tests\Support\AssetConnectFeatureTestCase;
use Tests\Support\Entities\FakeAssetEntity;
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

    public function testStorageWriteFailureExposesStorageSpecificException(): void
    {
        $disk = $this->createMock(StorageDiskInterface::class);
        $disk->method('name')->willReturn('public');
        $disk->method('visibility')->willReturn(AssetVisibility::PUBLIC);
        $disk->expects($this->once())
            ->method('writeStream')
            ->willThrowException(new RuntimeException('Adapter refused stream'));
        $disk->expects($this->once())
            ->method('delete');

        $this->assetConfig->storages['public'] = [
            'disk' => $disk,
        ];

        $entity = $this->createFakeEntity();
        $source = $this->createSourceFile('storage-failure.txt', 'document contents');

        try {
            $entity->addAsset($source)
                ->usingFileName('storage-failure.txt')
                ->preservingOriginal()
                ->toAssetCollection(FakeDocumentCollection::class);
        } catch (FileException $exception) {
            $this->assertSame(500, $exception->getCode());
            $this->assertSame('Adapter refused stream', $exception->getPrevious()?->getMessage());
            $this->assertStringContainsString('storage disk "public"', (string) $exception->errors[0]);
            $this->assertStringContainsString('fake-assets/documents/storage-failure.txt', (string) $exception->errors[0]);

            return;
        }

        $this->fail('Expected storage write failure exception.');
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

    public function testPersistedAssetExposesAccessorsCanBeUpdatedAndSerialized(): void
    {
        $entity = $this->createFakeEntity();

        $asset = $entity->addAsset($this->createSourceFile('accessor.txt', 'accessor text'))
            ->usingName('Accessor Document')
            ->usingFileName('accessor.txt')
            ->setOrder(4)
            ->withCustomProperties([
                'category' => 'manual',
                'tags'     => ['docs', 'public'],
            ])
            ->preservingOriginal()
            ->toAssetCollection(FakeDocumentCollection::class);

        $this->assertSame('public', $asset->getStorageDisk()->name());
        $this->assertSame(FakeDocumentCollection::class, $asset->getCollectionDefinitionClass());
        $this->assertInstanceOf(FakeDocumentCollection::class, $asset->getAssetCollectionDefinition());
        $this->assertSame(FakeAssetEntity::class, $asset->subject_entity_class);
        $this->assertInstanceOf(FakeAssetEntity::class, $asset->getSubjectEntity());
        $this->assertSame('fake-assets/documents/', $asset->path_dirname);
        $this->assertSame($this->storagePath($asset), $asset->local_path);
        $this->assertSame('/' . $asset->path, $asset->relative_path);
        $this->assertSame('/' . $asset->path, $asset->relative_path_for_url);
        $this->assertFalse($asset->is_protected_collection);
        $this->assertTrue($asset->isText());
        $this->assertSame([
            'category' => 'manual',
            'tags'     => ['docs', 'public'],
        ], $asset->getCustomProperties());

        $asset->name  = 'Updated Accessor Document';
        $asset->order = 9;
        $asset->setCustomProperty('reviewed', true);
        $asset->setInternalProperty('processing_job_id', 'job-123');
        $asset->metadata->internal->set('processor', ['name' => 'test']);

        $this->assertTrue($asset->save());

        $refetched = AssetModel::init(false)->find($asset->id);

        $this->assertInstanceOf(Asset::class, $refetched);
        $this->assertSame('Updated Accessor Document', $refetched->name);
        $this->assertSame(9, $refetched->order);
        $this->assertTrue($refetched->getCustomProperty('reviewed'));
        $this->assertSame('job-123', $refetched->getInternalProperty('processing_job_id'));
        $this->assertSame([
            'processing_job_id' => 'job-123',
            'processor'         => ['name' => 'test'],
        ], $refetched->getInternalProperties());

        $this->injectUrlRequest();

        $serialized = $refetched->jsonSerialize();

        $this->assertArrayNotHasKey('path', $serialized);
        $this->assertArrayNotHasKey('storage', $serialized);
        $this->assertArrayNotHasKey('internal', $serialized);
        $this->assertSame($refetched->id, $serialized['id']);
        $this->assertSame($refetched->file_name, $serialized['file_name']);
        $this->assertSame($refetched->getCustomProperties(), $serialized['custom_properties']);
        $this->assertStringContainsString('/assets/storage/fake-assets/documents/accessor.txt', (string) $serialized['url']);
        $this->assertStringContainsString('/assets/storage/fake-assets/documents/accessor.txt', (string) $serialized['url_relative']);
    }

    public function testEntityCanAddAssetsFromBase64AndPendingAsset(): void
    {
        $entity = $this->createFakeEntity();

        $base64Asset = $entity->addAssetFromBase64(base64_encode('base64 text'))
            ->usingName('Base64 Document')
            ->usingFileName('base64.txt')
            ->toAssetCollection(FakeDocumentCollection::class);

        $this->assertAssetWasStoredForEntity($base64Asset, $entity, 'fake_documents');
        $this->assertAssetFileContains($base64Asset, 'base64 text');
        $this->assertSame('Base64 Document', $base64Asset->name);

        $pendingSource = $this->createSourceFile('pending-source.txt', 'pending text');
        $pendingAsset  = PendingAsset::createFromFile($pendingSource);
        $pendingAsset
            ->usingName('Pending Document')
            ->usingFileName('pending.txt')
            ->setOrder(7)
            ->withCustomProperties([
                'source' => 'pending',
            ])
            ->preservingOriginal();

        $asset = $entity->addAssetFromPending($pendingAsset)
            ->toAssetCollection(FakeDocumentCollection::class);

        $this->assertAssetWasStoredForEntity($asset, $entity, 'fake_documents');
        $this->assertAssetFileContains($asset, 'pending text');
        $this->assertFileExists($pendingSource);
        $this->assertSame('Pending Document', $asset->name);
        $this->assertSame('pending.txt', $asset->file_name);
        $this->assertSame(7, $asset->order);
        $this->assertSame('pending', $asset->getCustomProperty('source'));
    }

    public function testEntityCanAddAssetsFromRequest(): void
    {
        $entity = $this->createFakeEntity();

        $uploadedFileOne = $this->createUploadedFileStub(
            'request one.txt',
            $this->createSourceFile('request-one-source.txt', 'request one'),
        );
        $uploadedFileTwo = $this->createUploadedFileStub(
            'request two.txt',
            $this->createSourceFile('request-two-source.txt', 'request two'),
        );

        $request = $this->createStub(IncomingRequest::class);
        $request->method('getFiles')->willReturn([
            'documents' => [$uploadedFileOne, $uploadedFileTwo],
        ]);

        Services::injectMock('request', $request);

        try {
            $multipleAdder = $entity->addAssetFromRequest('documents');
            $fieldNames    = [];

            $assetAdders = $multipleAdder->forEach(
                static function (UploadedFile $uploadedFile, AssetAdder $assetAdder, int|string $fieldName) use (&$fieldNames): void {
                    $fieldNames[] = $fieldName;
                    $assetAdder->usingName('Queued ' . $uploadedFile->getClientName());
                },
            );

            $this->assertContainsOnlyInstancesOf(AssetAdder::class, $assetAdders);
            $this->assertSame(['documents', 'documents'], $fieldNames);

            $assets = $multipleAdder->toAssetCollection(FakeDocumentCollection::class);
        } finally {
            $this->injectUrlRequest();
        }

        $this->assertCount(2, $assets);
        $this->assertAssetWasStoredForEntity($assets[0], $entity, 'fake_documents');
        $this->assertAssetWasStoredForEntity($assets[1], $entity, 'fake_documents');
        $this->assertSame('request-one.txt', $assets[0]->file_name);
        $this->assertSame('request-two.txt', $assets[1]->file_name);
    }

    public function testAssetStoragePathCanBeRemovedWithoutDeletingDatabaseRow(): void
    {
        $entity = $this->createFakeEntity();
        $asset  = $entity->addAsset($this->createSourceFile('remove-storage.txt', 'remove storage'))
            ->usingFileName('remove-storage.txt')
            ->preservingOriginal()
            ->toAssetCollection(FakeDocumentCollection::class);

        $storedPath = $this->storagePath($asset);

        $this->assertFileExists($storedPath);

        AssetPersistenceManager::removeStoragePath($asset->storage, $asset->path);

        $this->assertFileDoesNotExist($storedPath);
        $this->assertAssetRowExists($asset);
    }

    public function testAssetConnectCanSerializeFindAndRemoveCachedAssets(): void
    {
        $entity       = $this->createFakeEntity();
        $assetConnect = $entity->assetConnectInstance();

        $this->assertInstanceOf(AssetConnect::class, $assetConnect);

        $asset = $entity->addAsset($this->createSourceFile('cached.txt', 'cached'))
            ->usingFileName('cached.txt')
            ->preservingOriginal()
            ->toAssetCollection(FakeDocumentCollection::class);

        $this->assertSame($asset->id, $assetConnect->getAssetById($asset->id)->id);
        $this->assertCount(1, $entity->getAssets(FakeDocumentCollection::class));

        $restored = unserialize(serialize($assetConnect));

        $this->assertInstanceOf(AssetConnect::class, $restored);
        $this->assertSame($asset->id, $restored->getAssetById($asset->id)->id);

        $assetConnect->removeAssetById($asset->id);

        $this->assertSame([], $entity->getAssets(FakeDocumentCollection::class));
    }

    public function testAssetModelFiltersCanQueryStoredAssets(): void
    {
        $entity = $this->createFakeEntity();

        $alpha = $entity->addAsset($this->createSourceFile('alpha-filter.txt', 'alpha filter'))
            ->usingName('Alpha Manual')
            ->usingFileName('alpha-filter.txt')
            ->setOrder(3)
            ->withCustomProperties([
                'category' => 'manual',
                'approved' => true,
                'tags'     => ['docs', 'public'],
            ])
            ->preservingOriginal()
            ->toAssetCollection(FakeDocumentCollection::class);

        $entity->addAsset($this->createSourceFile('beta-filter.txt', 'beta filter value'))
            ->usingName('Beta Draft')
            ->usingFileName('beta-filter.txt')
            ->setOrder(8)
            ->withCustomProperties([
                'category' => 'draft',
                'tags'     => ['private'],
            ])
            ->preservingOriginal()
            ->toAssetCollection(FakeDocumentCollection::class);

        $this->assertAssetModelFilterFinds($alpha, static fn (AssetModel $model): AssetModel => $model->filterByName('Alpha Manual'));
        $this->assertAssetModelFilterFinds($alpha, static fn (AssetModel $model): AssetModel => $model->filterByFileName('alpha-filter.txt'));
        $this->assertAssetModelFilterFinds($alpha, static fn (AssetModel $model): AssetModel => $model->filterByMimeType($alpha->mime_type)->filterByName('Alpha Manual'));
        $this->assertAssetModelFilterFinds($alpha, static fn (AssetModel $model): AssetModel => $model->filterBySize($alpha->size)->filterByName('Alpha Manual'));
        $this->assertAssetModelFilterFinds($alpha, static fn (AssetModel $model): AssetModel => $model->filterByPath($alpha->path));
        $this->assertAssetModelFilterFinds($alpha, static fn (AssetModel $model): AssetModel => $model->filterByStorage('public')->filterByPath($alpha->path));
        $this->assertAssetModelFilterFinds($alpha, static fn (AssetModel $model): AssetModel => $model->filterByOrder(3));
        $this->assertAssetModelFilterFinds($alpha, static fn (AssetModel $model): AssetModel => $model->filterByProperty('category', 'manual'));
        $this->assertAssetModelFilterFinds($alpha, static fn (AssetModel $model): AssetModel => $model->filterByPropertyExists('approved'));
        $this->assertAssetModelFilterFinds($alpha, static fn (AssetModel $model): AssetModel => $model->filterByPropertyContains('tags', 'public'));
        $this->assertAssetModelFilterFinds($alpha, static fn (AssetModel $model): AssetModel => $model->filterByCreatedAt('2000-01-01 00:00:00', '>=')->filterByName('Alpha Manual'));
        $this->assertAssetModelFilterFinds($alpha, static fn (AssetModel $model): AssetModel => $model->filterByUpdatedAt('2000-01-01 00:00:00', '>=')->filterByName('Alpha Manual'));
        $this->assertAssetModelFilterFinds($alpha, static fn (AssetModel $model): AssetModel => $model->filterByNameLike('Alpha'));
        $this->assertAssetModelFilterFinds($alpha, static fn (AssetModel $model): AssetModel => $model->filterByFileNameLike('alpha-filter'));
        $this->assertAssetModelFilterFinds($alpha, static fn (AssetModel $model): AssetModel => $model->filterBySizeRange($alpha->size, $alpha->size));
        $this->assertAssetModelFilterFinds($alpha, static fn (AssetModel $model): AssetModel => $model->filterByDateRange('2000-01-01 00:00:00', '2999-01-01 00:00:00')->filterByName('Alpha Manual'));
        $this->assertAssetModelFilterFinds($alpha, static fn (AssetModel $model): AssetModel => $model->whereEntityType(FakeAssetEntity::class)->filterByName('Alpha Manual'));
    }

    /**
     * @param callable(AssetModel): AssetModel $filter
     */
    private function assertAssetModelFilterFinds(Asset $expectedAsset, callable $filter): void
    {
        $model = AssetModel::init(false);
        $asset = $filter($model)->first();

        $this->assertInstanceOf(Asset::class, $asset);
        $this->assertSame($expectedAsset->id, $asset->id);
    }

    /**
     * @return Stub&UploadedFile
     */
    private function createUploadedFileStub(string $clientName, string $path): UploadedFile
    {
        $uploadedFile = $this->createStub(UploadedFile::class);
        $uploadedFile->method('isValid')->willReturn(true);
        $uploadedFile->method('isFile')->willReturn(true);
        $uploadedFile->method('getClientName')->willReturn($clientName);
        $uploadedFile->method('getMimeType')->willReturn('text/plain');
        $uploadedFile->method('getSize')->willReturn((int) filesize($path));
        $uploadedFile->method('getCTime')->willReturn((int) filectime($path));
        $uploadedFile->method('getMTime')->willReturn((int) filemtime($path));
        $uploadedFile->method('getRealPath')->willReturn($path);

        return $uploadedFile;
    }

    private function injectUrlRequest(): void
    {
        $app            = new App();
        $app->baseURL   = 'https://example.com/';
        $app->indexPage = 'index.php';

        $request = $this->createStub(IncomingRequest::class);
        $request->method('getUri')->willReturn(new SiteURI($app));

        Services::injectMock('request', $request);
    }
}
