<?php

declare(strict_types=1);

namespace Tests\Feature;

use CodeIgniter\Files\File;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\SiteURI;
use CodeIgniter\Queue\Config\Services as QueueServices;
use CodeIgniter\Queue\Interfaces\QueueInterface;
use CodeIgniter\Queue\QueuePushResult;
use Config\App;
use Config\Services;
use Maniaba\AssetConnect\Asset\Asset;
use Maniaba\AssetConnect\Asset\AssetAdder;
use Maniaba\AssetConnect\Asset\AssetAdderMultiple;
use Maniaba\AssetConnect\Asset\AssetPersistenceManager;
use Maniaba\AssetConnect\AssetCollection\SetupAssetCollection;
use Maniaba\AssetConnect\AssetConnect;
use Maniaba\AssetConnect\AssetVariants\AssetVariant;
use Maniaba\AssetConnect\Enums\AssetVisibility;
use Maniaba\AssetConnect\Exceptions\AssetException;
use Maniaba\AssetConnect\Exceptions\FileException;
use Maniaba\AssetConnect\Exceptions\FileVariantException;
use Maniaba\AssetConnect\Exceptions\InvalidArgumentException;
use Maniaba\AssetConnect\Models\AssetModel;
use Maniaba\AssetConnect\Pending\PendingAsset;
use Maniaba\AssetConnect\Pending\PendingAssetManager;
use Maniaba\AssetConnect\Storage\Interfaces\StorageDiskInterface;
use PHPUnit\Framework\MockObject\Stub;
use RuntimeException;
use Tests\Support\AssetCollections\FakeAvatarCollection;
use Tests\Support\AssetCollections\FakeDocumentCollection;
use Tests\Support\AssetCollections\ImmediateVariantTestAssetCollection;
use Tests\Support\AssetCollections\MimeRestrictedTestAssetCollection;
use Tests\Support\AssetCollections\QueuedVariantTestAssetCollection;
use Tests\Support\AssetCollections\SingleFileTestAssetCollection;
use Tests\Support\AssetCollections\SizeLimitedTestAssetCollection;
use Tests\Support\AssetConnectFeatureTestCase;
use Tests\Support\Entities\FakeAssetEntity;
use Tests\Support\Files\FixedSizeFile;
use Tests\Support\Files\UnreadableFile;
use Tests\Support\Files\UnreadableStreamWrapper;
use Tests\Support\Files\UnsupportedAssetFileValue;
use Tests\Support\Models\FailingSaveAssetModel;
use Tests\Support\Models\FakeAssetEntityModel;
use Tests\Support\TestAssetCollection;

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

    public function testAssetPersistenceRejectsFilesOverCollectionLimit(): void
    {
        $entity = $this->createFakeEntity();

        try {
            $entity->addAsset($this->createSourceFile('oversized.txt', 'too large'))
                ->usingFileName('oversized.txt')
                ->preservingOriginal()
                ->toAssetCollection(SizeLimitedTestAssetCollection::class);
        } catch (AssetException $exception) {
            $this->assertSame(413, $exception->getCode(), 'Oversized files should be rejected with a payload-too-large status.');
            $this->assertSame('File size exceeds the maximum allowed size', $exception->getMessage(), 'Oversized files should use the file-size validation error.');
            $this->assertSame([], AssetModel::init(false)->where('collection', 'size_limited')->findAll(), 'Rejected oversized files should not create asset rows.');

            return;
        }

        $this->fail('Expected oversized file validation exception.');
    }

    public function testAssetPersistenceRejectsInvalidCollectionExtension(): void
    {
        $entity = $this->createFakeEntity();

        try {
            $entity->addAsset($this->createSourceFile('invalid-extension-source.txt', 'invalid extension'))
                ->usingFileName('invalid.pdf')
                ->preservingOriginal()
                ->toAssetCollection(FakeDocumentCollection::class);
        } catch (AssetException $exception) {
            $this->assertSame(400, $exception->getCode(), 'Invalid extensions should be rejected as a bad request.');
            $this->assertSame('Invalid file extension', $exception->getMessage(), 'Invalid extension validation should use its dedicated exception message.');
            $this->assertSame([], AssetModel::init(false)->where('file_name', 'invalid.pdf')->findAll(), 'Rejected invalid extensions should not create asset rows.');

            return;
        }

        $this->fail('Expected invalid extension validation exception.');
    }

    public function testAssetPersistenceRejectsInvalidCollectionMimeType(): void
    {
        $entity = $this->createFakeEntity();

        try {
            $entity->addAsset($this->createSourceFile('invalid-mime.txt', 'invalid mime'))
                ->usingFileName('invalid-mime.txt')
                ->preservingOriginal()
                ->toAssetCollection(MimeRestrictedTestAssetCollection::class);
        } catch (AssetException $exception) {
            $this->assertSame(400, $exception->getCode(), 'Invalid MIME types should be rejected as a bad request.');
            $this->assertSame('Invalid MIME type', $exception->getMessage(), 'Invalid MIME validation should use its dedicated exception message.');
            $this->assertSame([], AssetModel::init(false)->where('collection', 'mime_restricted')->findAll(), 'Rejected MIME types should not create asset rows.');

            return;
        }

        $this->fail('Expected invalid MIME validation exception.');
    }

    public function testAssetPersistenceRejectsUnsupportedAssetFileValue(): void
    {
        $entity  = $this->createFakeEntity();
        $asset   = $this->createPersistenceAsset($entity, new UnsupportedAssetFileValue(1), 'unsupported.txt');
        $manager = new AssetPersistenceManager(
            $entity,
            $asset,
            $this->createSetupAssetCollection(FakeDocumentCollection::class),
        );

        try {
            $manager->store();
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Invalid argument provided', $exception->getMessage(), 'Unsupported file values should use the package invalid argument exception.');
            $this->assertSame(['Unsupported asset type for storage.'], $exception->errors, 'Unsupported file values should explain that storage requires a file object.');

            return;
        }

        $this->fail('Expected unsupported asset type exception.');
    }

    public function testAssetPersistenceRejectsMissingSourceFile(): void
    {
        $entity  = $this->createFakeEntity();
        $missing = $this->sourceFilesRoot . DIRECTORY_SEPARATOR . 'missing-source.txt';
        $asset   = $this->createPersistenceAsset($entity, new FixedSizeFile($missing, 1), 'missing-source.txt');
        $manager = new AssetPersistenceManager(
            $entity,
            $asset,
            $this->createSetupAssetCollection(FakeDocumentCollection::class),
        );

        try {
            $manager->store();
        } catch (FileException $exception) {
            $this->assertSame(404, $exception->getCode(), 'Missing source files should be reported as not found.');
            $this->assertSame('File not found', $exception->getMessage(), 'Missing source files should use the storage file-not-found exception.');

            return;
        }

        $this->fail('Expected missing source file exception.');
    }

    public function testAssetPersistenceRejectsUnreadableSourceFile(): void
    {
        $streamWrapperRegistered = false;
        if (! in_array(UnreadableStreamWrapper::SCHEME, stream_get_wrappers(), true)) {
            $streamWrapperRegistered = stream_wrapper_register(UnreadableStreamWrapper::SCHEME, UnreadableStreamWrapper::class);
        }

        $entity = $this->createFakeEntity();
        $asset  = $this->createPersistenceAsset(
            $entity,
            new UnreadableFile(UnreadableStreamWrapper::path('unreadable-source.txt'), 10),
            'unreadable-source.txt',
            10,
        );
        $manager                = new AssetPersistenceManager($entity, $asset, $this->createSetupAssetCollection(FakeDocumentCollection::class));
        $previousErrorReporting = error_reporting();

        error_reporting($previousErrorReporting & ~E_WARNING);

        try {
            $manager->store();
        } catch (FileException $exception) {
            $this->assertSame(404, $exception->getCode(), 'Unreadable source files should be reported as not found by storage persistence.');
            $this->assertSame('File not found', $exception->getMessage(), 'Unreadable source files should use the same file-not-found exception as missing sources.');

            return;
        } finally {
            error_reporting($previousErrorReporting);
            if ($streamWrapperRegistered) {
                stream_wrapper_unregister(UnreadableStreamWrapper::SCHEME);
            }
        }

        $this->fail('Expected unreadable source file exception.');
    }

    public function testAssetPersistenceFallsBackToDefaultPublicStorageWhenCollectionHasNoStorage(): void
    {
        $entity = $this->createFakeEntity();
        $asset  = $entity->addAsset($this->createSourceFile('default-storage.txt', 'default storage'))
            ->usingFileName('default-storage.txt')
            ->preservingOriginal()
            ->toAssetCollection(TestAssetCollection::class);

        $this->assertSame('public', $asset->storage, 'Collections without explicit storage should use the default public storage disk.');
        $this->assertAssetWasStoredForEntity($asset, $entity, 'test_collection');
    }

    public function testAssetPersistenceCleansStoredFileWhenDatabaseSaveFails(): void
    {
        $this->assetConfig->assetModel = FailingSaveAssetModel::class;
        $entity                        = $this->createFakeEntity();
        $expectedStoredPath            = 'fake-assets/documents/database-failure.txt';

        try {
            $entity->addAsset($this->createSourceFile('database-failure.txt', 'database failure'))
                ->usingFileName('database-failure.txt')
                ->preservingOriginal()
                ->toAssetCollection(FakeDocumentCollection::class);
        } catch (AssetException $exception) {
            $this->assertSame(
                ['storage' => 'Unable to update test asset storage.'],
                $exception->errors,
                'Database save failures should expose the model validation errors.',
            );
            $this->assertFileDoesNotExist(
                $this->storagePathFor('public', $expectedStoredPath),
                'Storage cleanup should remove the file written before the database save failure.',
            );

            return;
        } finally {
            $this->assetConfig->assetModel = AssetModel::class;
        }

        $this->fail('Expected database save failure exception.');
    }

    public function testAssetPersistenceLogsAndKeepsOriginalFailureWhenStorageCleanupFails(): void
    {
        $disk = $this->createMock(StorageDiskInterface::class);
        $disk->method('name')->willReturn('public');
        $disk->method('visibility')->willReturn(AssetVisibility::PUBLIC);
        $disk->method('localPath')->willReturn(null);
        $disk->expects($this->once())
            ->method('writeStream')
            ->with('fake-assets/documents/cleanup-delete-failure.txt');
        $disk->expects($this->once())
            ->method('delete')
            ->with('fake-assets/documents/cleanup-delete-failure.txt')
            ->willThrowException(new RuntimeException('delete failed'));

        $this->assetConfig->storages['public'] = [
            'disk' => $disk,
        ];
        $this->assetConfig->assetModel = FailingSaveAssetModel::class;
        $entity                        = $this->createFakeEntity();

        try {
            $entity->addAsset($this->createSourceFile('cleanup-delete-failure.txt', 'cleanup failure'))
                ->usingFileName('cleanup-delete-failure.txt')
                ->preservingOriginal()
                ->toAssetCollection(FakeDocumentCollection::class);
        } catch (AssetException $exception) {
            $this->assertSame(
                ['storage' => 'Unable to update test asset storage.'],
                $exception->errors,
                'Cleanup failures should not replace the original database failure.',
            );

            return;
        } finally {
            $this->assetConfig->assetModel = AssetModel::class;
        }

        $this->fail('Expected original database save failure exception.');
    }

    public function testAssetPersistenceProcessesImmediateVariants(): void
    {
        $entity = $this->createFakeEntity();
        $asset  = $entity->addAsset($this->createSourceFile('variant-source.txt', 'variant source'))
            ->usingFileName('variant-source.txt')
            ->preservingOriginal()
            ->toAssetCollection(ImmediateVariantTestAssetCollection::class);

        $this->assertAssetWasStoredForEntity($asset, $entity, 'immediate_variants');

        $variant = $asset->metadata->assetVariant->getAssetVariant('preview');

        $this->assertInstanceOf(AssetVariant::class, $variant, 'Immediate variant collections should attach the configured variant metadata.');
        $this->assertTrue($variant->processed, 'Immediate variants should be processed before the asset is returned.');
        $this->assertGreaterThan(0, $variant->size, 'Immediate variants should store their final file size.');
        $this->assertSame(
            'preview for variant-source.txt',
            file_get_contents($this->storagePathFor($variant->storage, $variant->path)),
            'Immediate variant processing should write the configured variant file contents.',
        );
    }

    public function testAssetPersistenceCleansStoredAssetAndDatabaseRowWhenQueueingVariantsFails(): void
    {
        $queue = $this->createMock(QueueInterface::class);
        $queue->expects($this->once())
            ->method('push')
            ->with(
                'asset_queue',
                'asset_connect',
                $this->callback(static fn (array $payload): bool => ($payload['definition'] ?? null) === QueuedVariantTestAssetCollection::class
                        && isset($payload['assetId'])
                        && is_int($payload['assetId'])
                        && $payload['assetId'] > 0),
            )
            ->willReturn(QueuePushResult::failure('queue disabled'));

        QueueServices::injectMock('queue', $queue);

        $entity             = $this->createFakeEntity();
        $expectedStoredPath = 'fake-assets/queued-variants/queue-failure.txt';

        try {
            $entity->addAsset($this->createSourceFile('queue-failure.txt', 'queue failure'))
                ->usingFileName('queue-failure.txt')
                ->preservingOriginal()
                ->toAssetCollection(QueuedVariantTestAssetCollection::class);
        } catch (FileVariantException $exception) {
            $this->assertSame(
                ['Failed to queue asset variants processing.'],
                $exception->errors,
                'Queue failures should expose the asset variant queueing error.',
            );
            $this->assertFileDoesNotExist(
                $this->storagePathFor('public', $expectedStoredPath),
                'Queueing failures after save should remove the already stored asset file.',
            );
            $this->assertNull(
                $this->db->table($this->tables['assets'])
                    ->where('file_name', 'queue-failure.txt')
                    ->get()
                    ->getRowArray(),
                'Queueing failures after save should force-delete the inserted asset row.',
            );

            return;
        } finally {
            QueueServices::resetSingle('queue');
        }

        $this->fail('Expected queued variant processing exception.');
    }

    public function testAssetPersistenceKeepsOnlyLatestAssetInSingleFileCollection(): void
    {
        $entity = $this->createFakeEntity();

        $first = $entity->addAsset($this->createSourceFile('single-old.txt', 'old'))
            ->usingFileName('single-old.txt')
            ->preservingOriginal()
            ->toAssetCollection(SingleFileTestAssetCollection::class);

        $this->db->table($this->tables['assets'])
            ->where('id', $first->id)
            ->update(['created_at' => '2000-01-01 00:00:00']);

        $second = $entity->addAsset($this->createSourceFile('single-new.txt', 'new'))
            ->usingFileName('single-new.txt')
            ->preservingOriginal()
            ->toAssetCollection(SingleFileTestAssetCollection::class);

        $assets = $this->assertEntityAssetCount($entity, 1, SingleFileTestAssetCollection::class);

        $this->assertSame($second->id, $assets[0]->id, 'Single file collections should keep the newest asset in the entity cache.');
        $this->assertSame('single-new.txt', $assets[0]->file_name, 'Single file collections should keep the newest asset row active.');
        $this->assertAssetWasSoftDeleted($first);
        $this->assertAssetRowExists($second);
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

    public function testEntityDeletesStoredPendingAssetAfterConvertingPendingId(): void
    {
        $this->assetConfig->pendingSecurityToken = null;

        $entity        = $this->createFakeEntity();
        $pendingSource = $this->createSourceFile('stored-pending-source.txt', 'stored pending text');
        $pendingAsset  = PendingAsset::createFromFile($pendingSource);
        $pendingAsset
            ->usingName('Stored Pending Document')
            ->usingFileName('stored-pending.txt')
            ->withCustomProperty('source', 'stored-pending');

        $manager = PendingAssetManager::make();
        $manager->store($pendingAsset);

        $pendingAssetId = $pendingAsset->id;
        $this->assertInstanceOf(PendingAsset::class, $manager->fetchById($pendingAssetId));

        $asset = $entity->addAssetFromPending($pendingAssetId)
            ->toAssetCollection(FakeDocumentCollection::class);

        $this->assertAssetWasStoredForEntity($asset, $entity, 'fake_documents');
        $this->assertAssetFileContains($asset, 'stored pending text');
        $this->assertSame('Stored Pending Document', $asset->name);
        $this->assertSame('stored-pending.txt', $asset->file_name);
        $this->assertSame('stored-pending', $asset->getCustomProperty('source'));
        $this->assertNull($manager->fetchById($pendingAssetId));
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

    public function testMultipleAssetAdderAcceptsSingleUploadedFileValue(): void
    {
        $entity       = $this->createFakeEntity();
        $uploadedFile = $this->createUploadedFileStub(
            'single request.txt',
            $this->createSourceFile('single-request-source.txt', 'single request'),
        );
        $multipleAdder = $this->createMultipleAssetAdder([
            'document' => $uploadedFile,
        ], $entity);

        $assetAdders = $multipleAdder->forEach();

        $this->assertCount(1, $assetAdders, 'forEach should wrap a single UploadedFile value into a list.');
        $this->assertContainsOnlyInstancesOf(AssetAdder::class, $assetAdders, 'forEach should return asset adders for single UploadedFile values.');

        $assets = $multipleAdder->toAssetCollection(FakeDocumentCollection::class);

        $this->assertCount(1, $assets, 'toAssetCollection should wrap a single UploadedFile value into a list.');
        $this->assertAssetWasStoredForEntity($assets[0], $entity, 'fake_documents');
        $this->assertSame('single-request.txt', $assets[0]->file_name, 'Single uploaded file should be stored with its sanitized client file name.');
    }

    public function testMultipleAssetAdderRejectsInvalidUploadedFileItem(): void
    {
        $multipleAdder = $this->createMultipleAssetAdder([
            'document' => ['not an uploaded file'],
        ], $this->createFakeEntity());

        try {
            $multipleAdder->forEach();
            $this->fail('forEach should reject items that are not UploadedFile instances.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Invalid argument provided', $exception->getMessage(), 'Invalid item should use the package invalid argument message.');
            $this->assertSame(['Expected UploadedFile, got string'], $exception->errors, 'Invalid item should report the actual value type.');
        }
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

    public function testAssetCanBeTransferredToAnotherStorageWithVariants(): void
    {
        $entity = $this->createFakeEntity();
        $asset  = $entity->addAsset($this->createSourceFile('transfer-storage.txt', 'transfer source'))
            ->usingFileName('transfer-storage.txt')
            ->preservingOriginal()
            ->toAssetCollection(FakeDocumentCollection::class);

        $variant = new AssetVariant([
            'name'      => 'preview',
            'storage'   => $asset->storage,
            'path'      => 'fake-assets/documents/variants/transfer-storage-preview.txt',
            'size'      => 16,
            'processed' => true,
        ]);

        $asset->metadata->assetVariant->addAssetVariant($variant);
        $asset->getStorageDisk()->write($variant->path, 'variant contents');

        $this->assertTrue($asset->save());

        $sourceAssetPath   = $this->storagePath($asset);
        $sourceVariantPath = $this->storagePathFor('public', $variant->path);

        $this->assertFileExists($sourceAssetPath);
        $this->assertFileExists($sourceVariantPath);

        $result = $asset->transferToStorage('protected');

        $this->assertSame($asset, $result);
        $this->assertSame('protected', $asset->storage);
        $this->assertSame('transfer source', file_get_contents($this->storagePath($asset)));
        $this->assertFileDoesNotExist($sourceAssetPath);
        $this->assertFileDoesNotExist($sourceVariantPath);

        $transferredVariant = $asset->metadata->assetVariant->getAssetVariant('preview');

        $this->assertInstanceOf(AssetVariant::class, $transferredVariant);
        $this->assertSame('protected', $transferredVariant->storage);
        $this->assertSame('variant contents', file_get_contents($this->storagePathFor('protected', $transferredVariant->path)));

        $refetched = AssetModel::init(false)->find($asset->id);

        $this->assertInstanceOf(Asset::class, $refetched);
        $this->assertSame('protected', $refetched->storage);
        $this->assertSame($asset->path, $refetched->path);

        $refetchedVariant = $refetched->metadata->assetVariant->getAssetVariant('preview');

        $this->assertInstanceOf(AssetVariant::class, $refetchedVariant);
        $this->assertSame('protected', $refetchedVariant->storage);
    }

    public function testAssetTransferCanKeepSourceFiles(): void
    {
        $entity = $this->createFakeEntity();
        $asset  = $entity->addAsset($this->createSourceFile('copy-storage.txt', 'copy source'))
            ->usingFileName('copy-storage.txt')
            ->preservingOriginal()
            ->toAssetCollection(FakeDocumentCollection::class);

        $sourceAssetPath = $this->storagePath($asset);

        $asset->transferToStorage('protected', deleteSource: false);

        $this->assertSame('protected', $asset->storage);
        $this->assertFileExists($sourceAssetPath);
        $this->assertSame('copy source', file_get_contents($sourceAssetPath));
        $this->assertSame('copy source', file_get_contents($this->storagePath($asset)));
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

    private function createSetupAssetCollection(string $collection): SetupAssetCollection
    {
        return (new SetupAssetCollection())->setDefaultCollectionDefinition($collection);
    }

    private function createPersistenceAsset(
        FakeAssetEntity $entity,
        File|UnsupportedAssetFileValue $file,
        string $fileName,
        int $size = 1,
        string $mimeType = 'text/plain',
    ): Asset {
        return Asset::create([
            'file'        => $file,
            'file_name'   => $fileName,
            'name'        => pathinfo($fileName, PATHINFO_FILENAME),
            'mime_type'   => $mimeType,
            'size'        => $size,
            'entity_id'   => $entity->id,
            'entity_type' => $entity,
            'order'       => 0,
        ]);
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

    /**
     * @param array<string, mixed> $uploadedFiles
     */
    private function createMultipleAssetAdder(array $uploadedFiles, FakeAssetEntity $entity): AssetAdderMultiple
    {
        return new AssetAdderMultiple($uploadedFiles, $entity);
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
