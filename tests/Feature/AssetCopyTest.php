<?php

declare(strict_types=1);

namespace Tests\Feature;

use CodeIgniter\Events\Events;
use CodeIgniter\I18n\Time;
use CodeIgniter\Queue\Config\Services as QueueServices;
use CodeIgniter\Queue\Interfaces\QueueInterface;
use CodeIgniter\Queue\QueuePushResult;
use Maniaba\AssetConnect\Asset\Asset;
use Maniaba\AssetConnect\Asset\Interfaces\AssetCollectionDefinitionInterface;
use Maniaba\AssetConnect\AssetCollection\DefaultAssetCollection;
use Maniaba\AssetConnect\AssetVariants\AssetVariant;
use Maniaba\AssetConnect\Enums\AssetVisibility;
use Maniaba\AssetConnect\Events\AssetCreated;
use Maniaba\AssetConnect\Exceptions\AssetException;
use Maniaba\AssetConnect\Exceptions\FileException;
use Maniaba\AssetConnect\Exceptions\FileVariantException;
use Maniaba\AssetConnect\Exceptions\InvalidArgumentException;
use Maniaba\AssetConnect\Jobs\AssetConnectJob;
use Maniaba\AssetConnect\Models\AssetModel;
use Maniaba\AssetConnect\Storage\Interfaces\StorageDiskInterface;
use org\bovigo\vfs\vfsStream;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\Support\AssetCollections\FakeAvatarCollection;
use Tests\Support\AssetCollections\FakeDocumentCollection;
use Tests\Support\AssetCollections\ImmediateVariantTestAssetCollection;
use Tests\Support\AssetCollections\MimeRestrictedTestAssetCollection;
use Tests\Support\AssetCollections\ProtectedTestAssetCollection;
use Tests\Support\AssetCollections\QueuedVariantTestAssetCollection;
use Tests\Support\AssetCollections\SingleFileTestAssetCollection;
use Tests\Support\AssetCollections\SizeLimitedTestAssetCollection;
use Tests\Support\AssetConnectFeatureTestCase;
use Tests\Support\Entities\CopyTargetEntity;
use Tests\Support\Models\FailingSaveAssetModel;

/**
 * @internal
 */
final class AssetCopyTest extends AssetConnectFeatureTestCase
{
    #[Override]
    protected function configureDatabaseTest(): void
    {
        parent::configureDatabaseTest();

        $this->assetConfig->collectionKeyDefinitions[ProtectedTestAssetCollection::class] = 'protected_test';
    }

    public function testCopiesMetadataToAnotherEntityTypeUsingItsConfiguredPrimaryKey(): void
    {
        $source = $this->createFakeEntity();
        $asset  = $source->addAsset($this->createSourceFile('original.txt', 'original content'))
            ->usingName('Original title')
            ->setOrder(12)
            ->withCustomProperties(['nested' => ['verified' => true, 'tags' => ['one', 'two']]])
            ->toAssetCollection();
        $asset->setInternalProperty('storage_reference', $asset->path);
        $asset->created_at = Time::parse('2020-01-01');
        $asset->updated_at = Time::parse('2020-01-02');

        $target = new CopyTargetEntity(['target_id' => 123]);
        $copy   = $asset->copyToEntity($target);
        $row    = AssetModel::init(false)->find($copy->id);

        $this->assertInstanceOf(Asset::class, $row);
        $this->assertNotSame($asset->id, $row->id);
        $this->assertSame(123, $row->entity_id);
        $this->assertSame('copy_target', $row->entity_type);
        $this->assertSame($asset->collection, $row->collection);
        $this->assertSame($asset->storage, $row->storage);
        $this->assertSame($asset->file_name, $row->file_name);
        $this->assertSame($asset->name, $row->name);
        $this->assertSame($asset->mime_type, $row->mime_type);
        $this->assertSame($asset->size, $row->size);
        $this->assertSame(12, $row->order);
        $this->assertSame($asset->getCustomProperties(), $row->getCustomProperties());
        $this->assertSame([], $row->getInternalProperties());
        $this->assertNotSame($asset->created_at, $row->created_at);
        $this->assertNotSame($asset->updated_at, $row->updated_at);
        $this->assertNotSame($asset->path, $row->path);
        $this->assertStringStartsWith('fake-assets/documents/', $row->path);

        $copy->setCustomProperty('nested', []);
        $this->assertTrue($asset->getCustomProperty('nested')['verified']);
        $this->assertAssetFileContains($asset, 'original content');
        $this->assertAssetFileContains($row, 'original content');
    }

    public function testCopiesAllOrOneCollectionAndKeepsSourcesIndependentOfTheCache(): void
    {
        $source = $this->createFakeEntity();
        $target = $this->createFakeEntity();
        $avatar = $source->addAssetFromString('avatar', 'avatar.txt')->setOrder(20)->toAssetCollection(FakeAvatarCollection::class);
        $doc    = $source->addAssetFromString('document', 'doc.txt')->setOrder(3)->toAssetCollection();
        $this->assertEntityAssetCount($source, 2);
        $source->assetConnectInstance()?->removeAssetById($doc->id);
        $this->assertEntityAssetCount($source, 1);

        $copies = $source->copyAssetsTo($target);
        $this->assertSame([$doc->collection, $avatar->collection], array_column($copies, 'collection'));
        $this->assertSame([3, 20], array_column($copies, 'order'));
        $this->assertSame([$target->id, $target->id], array_column($copies, 'entity_id'));
        $this->assertAssetFileContains($copies[0], 'document');
        $this->assertAssetFileContains($copies[1], 'avatar');

        $filtered = $source->copyAssetsTo($target, collection: FakeDocumentCollection::class);
        $this->assertCount(1, $filtered);
        $this->assertSame($doc->collection, $filtered[0]->collection);
        $this->assertNotSame($copies[0]->path, $filtered[0]->path);
        $this->assertEntityAssetCount($target, 3);
    }

    public function testCanStreamAcrossAdaptersAndUsesTheSourceDiskWhenOmitted(): void
    {
        $remote     = $this->registerAlternateStorage();
        $source     = $this->createFakeEntity();
        $target     = $this->createFakeEntity();
        $asset      = $source->addAssetFromString('cross disk', 'stream.txt')->toAssetCollection(DefaultAssetCollection::class);
        $remoteCopy = $asset->copyToEntity($target, storage: 'alternate');

        $this->assertSame('alternate', $remoteCopy->storage);
        $this->assertNull($remoteCopy->local_path);
        $this->assertSame('cross disk', $remote->read($remoteCopy->path));

        $sameDisk = $remoteCopy->copyToEntity($source);
        $this->assertSame('alternate', $sameDisk->storage);
        $this->assertNotSame($remoteCopy->path, $sameDisk->path);
        $this->assertSame('cross disk', $remote->read($sameDisk->path));

        $localCopies = $target->copyAssetsTo($source, storage: 'public');
        $this->assertCount(1, $localCopies);
        $this->assertAssetFileContains($localCopies[0], 'cross disk');

        $remoteCopies = $target->copyAssetsTo($source, storage: 'alternate');
        $this->assertCount(1, $remoteCopies);
        $this->assertSame('alternate', $remoteCopies[0]->storage);
        $this->assertSame('cross disk', $remote->read($remoteCopies[0]->path));
        $this->assertAssetFileContains($asset, 'cross disk');
    }

    public function testCopiesIntoAnotherCollectionAndRegeneratesItsVariants(): void
    {
        $source = $this->createFakeEntity();
        $target = $this->createFakeEntity();
        $asset  = $source->addAssetFromString('original', 'variants.txt')->toAssetCollection();
        $copy   = $asset->copyToEntity($target, collection: ImmediateVariantTestAssetCollection::class, storage: 'public');

        $this->assertSame('immediate_variants', $copy->collection);
        $this->assertSame([], $asset->metadata->assetVariant->getVariants());
        $variant = $copy->metadata->assetVariant->getAssetVariant('preview');
        $this->assertInstanceOf(AssetVariant::class, $variant);
        $this->assertTrue($variant->processed);
        $this->assertSame('preview for variants.txt', $variant->getStorageDisk()->read($variant->path));

        $secondCopy    = $copy->copyToEntity($target);
        $secondVariant = $secondCopy->metadata->assetVariant->getAssetVariant('preview');
        $this->assertInstanceOf(AssetVariant::class, $secondVariant);
        $this->assertNotSame($variant->path, $secondVariant->path);
    }

    public function testQueuesVariantsForTheNewAssetId(): void
    {
        $source = $this->createFakeEntity();
        $target = $this->createFakeEntity();
        $asset  = $source->addAssetFromString('original', 'queued.txt')->toAssetCollection();

        $job   = [];
        $queue = $this->createMock(QueueInterface::class);
        $queue->expects($this->once())->method('push')
            ->willReturnCallback(static function (string $queue, string $handler, array $data) use (&$job): QueuePushResult {
                $job = $data;

                return QueuePushResult::success(1);
            });

        QueueServices::injectMock('queue', $queue);

        try {
            $copy = $asset->copyToEntity($target, collection: QueuedVariantTestAssetCollection::class);
        } finally {
            QueueServices::resetSingle('queue');
        }

        $this->assertSame($copy->id, $job['assetId']);
        $this->assertSame(QueuedVariantTestAssetCollection::class, $job['definition']);
        $variant = $copy->metadata->assetVariant->getAssetVariant('queued');
        $this->assertInstanceOf(AssetVariant::class, $variant);
        $this->assertFalse($variant->processed);

        (new AssetConnectJob($job))->process();
        $this->assertSame('queued for queued.txt', $variant->getStorageDisk()->read($variant->path));
    }

    public function testRejectsUnknownStorageForSingleAndBatchCopies(): void
    {
        $source = $this->createFakeEntity();
        $target = $this->createFakeEntity();
        $asset  = $source->addAssetFromString('source', 'unknown.txt')->toAssetCollection();

        foreach ([
            static fn () => $asset->copyToEntity($target, storage: 'unknown'),
            static fn () => $source->copyAssetsTo($target, storage: 'unknown'),
        ] as $copy) {
            try {
                $copy();
                $this->fail('Expected the disk to be rejected.');
            } catch (InvalidArgumentException $exception) {
                $this->assertStringContainsString('unknown', (string) $exception->errors[0]);
            }
        }

        $this->assertCount(1, AssetModel::init(false)->findAll());
    }

    /**
     * @param class-string<AssetCollectionDefinitionInterface> $collection
     */
    #[DataProvider('provideValidatesDestinationCollectionConstraints')]
    public function testValidatesDestinationCollectionConstraints(string $collection, ?string $storage, int $code): void
    {
        $this->registerAlternateStorage();
        $source = $this->createFakeEntity();
        $target = $this->createFakeEntity();
        $asset  = $source->addAssetFromString('source', 'constraints.txt')->toAssetCollection();

        $this->expectException(AssetException::class);
        $this->expectExceptionCode($code);

        $asset->copyToEntity($target, $collection, $storage);
    }

    /**
     * @return iterable<string, array{class-string<AssetCollectionDefinitionInterface>, ?string, int}>
     */
    public static function provideValidatesDestinationCollectionConstraints(): iterable
    {
        yield 'pinned storage' => [FakeDocumentCollection::class, 'alternate', 400];

        yield 'protected visibility' => [ProtectedTestAssetCollection::class, 'public', 400];

        yield 'implicit storage with incompatible visibility' => [ProtectedTestAssetCollection::class, null, 400];

        yield 'size' => [SizeLimitedTestAssetCollection::class, null, 413];

        yield 'MIME type' => [MimeRestrictedTestAssetCollection::class, null, 400];
    }

    public function testValidatesTheDestinationFileExtension(): void
    {
        $source = $this->createFakeEntity();
        $target = $this->createFakeEntity();
        $asset  = $source->addAssetFromString('source', 'wrong.csv')->toAssetCollection(DefaultAssetCollection::class);

        $this->expectException(AssetException::class);
        $this->expectExceptionMessage('Invalid file extension');

        $asset->copyToEntity($target, FakeDocumentCollection::class);
    }

    public function testCanSelectAProtectedDiskForAnAuthorizableCollection(): void
    {
        $source = $this->createFakeEntity();
        $target = $this->createFakeEntity();
        $asset  = $source->addAssetFromString('source', 'protected-copy.txt')->toAssetCollection();

        $copy = $asset->copyToEntity($target, collection: ProtectedTestAssetCollection::class, storage: 'protected');

        $this->assertSame('protected', $copy->storage);
        $this->assertTrue($copy->is_protected_collection);
        $this->assertAssetFileContains($copy, 'source');
    }

    public function testBatchRollsBackFilesWhenAStorageWriteFailsPartwayThrough(): void
    {
        $paths  = [];
        $remote = $this->registerAlternateStorage(static function (string $path) use (&$paths): void {
            $paths[] = $path;
            if (count($paths) === 2) {
                throw new RuntimeException('Storage failed after writing the second file.');
            }
        });

        $source = $this->createFakeEntity();
        $target = $this->createFakeEntity();
        $first  = $source->addAssetFromString('first', 'first.txt')->toAssetCollection(DefaultAssetCollection::class);
        $second = $source->addAssetFromString('second', 'second.txt')->toAssetCollection(DefaultAssetCollection::class);

        try {
            $source->copyAssetsTo($target, storage: 'alternate');
            $this->fail('Expected the second write to fail.');
        } catch (FileException $exception) {
            $this->assertStringContainsString('second file', (string) $exception->getPrevious()?->getMessage());
        }

        $this->assertCount(2, $paths);
        $this->assertCount(2, AssetModel::init(false)->findAll());
        $this->assertEntityAssetCount($target, 0);
        $this->assertAssetFileContains($first, 'first');
        $this->assertAssetFileContains($second, 'second');

        foreach ($paths as $path) {
            $this->assertFalse($remote->fileExists($path));
        }
    }

    public function testBatchRollsBackRowsVariantsAndCacheWhenAListenerThrows(): void
    {
        $source = $this->createFakeEntity();
        $target = $this->createFakeEntity();
        $first  = $source->addAssetFromString('first', 'variant-first.txt')->toAssetCollection(ImmediateVariantTestAssetCollection::class);
        $second = $source->addAssetFromString('second', 'variant-second.txt')->setOrder(2)->toAssetCollection(ImmediateVariantTestAssetCollection::class);
        $seen   = [];

        Events::on(AssetCreated::name(), static function (AssetCreated $event) use (&$seen): void {
            $seen[] = $event->getAsset();
            if (count($seen) === 2) {
                throw new RuntimeException('Second copy could not be published.');
            }
        });

        try {
            $source->copyAssetsTo($target);
            $this->fail('Expected listener failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Second copy could not be published.', $exception->getMessage());
        } finally {
            Events::removeAllListeners(AssetCreated::name());
        }

        $this->assertCount(2, $seen);
        $this->assertCount(2, AssetModel::init(false)->withDeleted()->findAll());

        foreach ($seen as $copy) {
            $this->assertFalse($copy->getStorageDisk()->fileExists($copy->path));
            $variant = $copy->metadata->assetVariant->getAssetVariant('preview');
            $this->assertInstanceOf(AssetVariant::class, $variant);
            $this->assertFalse($variant->getStorageDisk()->fileExists($variant->path));
        }

        $this->assertEntityAssetCount($target, 0);
        $this->assertAssetFileContains($first, 'first');
        $this->assertAssetFileContains($second, 'second');
    }

    public function testCleansTheWrittenFileWhenTheModelRejectsACopy(): void
    {
        $path   = null;
        $remote = $this->registerAlternateStorage(static function (string $writtenPath) use (&$path): void {
            $path = $writtenPath;
        });
        $source = $this->createFakeEntity();
        $target = $this->createFakeEntity();
        $asset  = $source->addAssetFromString('source', 'save-error.txt')->toAssetCollection(DefaultAssetCollection::class);

        $this->assetConfig->assetModel = FailingSaveAssetModel::class;

        try {
            $asset->copyToEntity($target, storage: 'alternate');
            $this->fail('Expected the model to reject the copy.');
        } catch (AssetException $exception) {
            $this->assertSame(500, $exception->getCode());
        }

        $this->assertIsString($path);
        $this->assertFalse($remote->fileExists($path));
        $this->assertCount(1, AssetModel::init(false)->withDeleted()->findAll());
        $this->assertAssetFileContains($asset, 'source');
    }

    public function testOneCopyRollsBackWhenQueueingFails(): void
    {
        $source = $this->createFakeEntity();
        $target = $this->createFakeEntity();
        $asset  = $source->addAssetFromString('source', 'queue-error.txt')->toAssetCollection();
        $queue  = $this->createMock(QueueInterface::class);
        $queue->expects($this->once())->method('push')->willReturn(QueuePushResult::failure('Unavailable'));
        QueueServices::injectMock('queue', $queue);

        try {
            $asset->copyToEntity($target, QueuedVariantTestAssetCollection::class);
            $this->fail('Expected queue failure.');
        } catch (FileVariantException $exception) {
            $this->assertSame(['Failed to queue asset variants processing.'], $exception->errors);
        } finally {
            QueueServices::resetSingle('queue');
        }

        $this->assertCount(1, AssetModel::init(false)->withDeleted()->findAll());
        $this->assertEntityAssetCount($target, 0);
        $this->assertAssetFileContains($asset, 'source');
    }

    public function testRetentionReplacesAnOlderTargetButNeverDeletesTheSource(): void
    {
        $source = $this->createFakeEntity();
        $target = $this->createFakeEntity();
        $old    = $target->addAssetFromString('old', 'old.txt')->toAssetCollection(SingleFileTestAssetCollection::class);
        $asset  = $source->addAssetFromString('original', 'single.txt')->toAssetCollection(SingleFileTestAssetCollection::class);
        $copy   = $asset->copyToEntity($target);

        $this->assertAssetWasSoftDeleted($old);
        $this->assertSame($copy->id, $target->getFirstAsset()?->id);

        try {
            $source->copyAssetsTo($source);
            $this->fail('Expected copy to reject eviction of its source.');
        } catch (AssetException $exception) {
            $this->assertSame(409, $exception->getCode());
        }

        $this->assertSame($asset->id, $source->getFirstAsset()?->id);
        $this->assertCount(2, AssetModel::init(false)->findAll());
        $this->assertAssetFileContains($asset, 'original');
        $this->assertAssetFileContains($copy, 'original');
    }

    public function testDeletingEitherOriginalOrCopyLeavesTheOtherFilesIntact(): void
    {
        $source = $this->createFakeEntity();
        $target = $this->createFakeEntity();
        $asset  = $source->addAssetFromString('original', 'delete.txt')->toAssetCollection(ImmediateVariantTestAssetCollection::class);
        $copy   = $asset->copyToEntity($target);
        $job    = new AssetConnectJob([]);

        $this->assertTrue($target->deleteAssets());
        $job->cleanGarbage();
        $this->assertFalse($copy->getStorageDisk()->fileExists($copy->path));
        $this->assertAssetFileContains($asset, 'original');

        $copy = $asset->copyToEntity($target);
        $this->assertTrue($source->deleteAssets());
        $job->cleanGarbage();
        $this->assertFalse($asset->getStorageDisk()->fileExists($asset->path));
        $this->assertAssetFileContains($copy, 'original');
        $variant = $copy->metadata->assetVariant->getAssetVariant('preview');
        $this->assertInstanceOf(AssetVariant::class, $variant);
        $this->assertTrue($variant->getStorageDisk()->fileExists($variant->path));
    }

    /**
     * @param (callable(string): void)|null $afterWrite
     */
    private function registerAlternateStorage(?callable $afterWrite = null): StorageDiskInterface
    {
        $root = vfsStream::setup('asset-copy')->url();
        $disk = $this->createStub(StorageDiskInterface::class);
        $disk->method('name')->willReturn('alternate');
        $disk->method('visibility')->willReturn(AssetVisibility::PUBLIC);
        $disk->method('localPath')->willReturn(null);
        $disk->method('fileExists')->willReturnCallback(static fn (string $path): bool => is_file($root . '/' . $path));
        $disk->method('fileSize')->willReturnCallback(static fn (string $path): int => (int) filesize($root . '/' . $path));
        $disk->method('read')->willReturnCallback(static fn (string $path): string => (string) file_get_contents($root . '/' . $path));
        $disk->method('readStream')->willReturnCallback(static fn (string $path) => fopen($root . '/' . $path, 'rb'));
        $disk->method('delete')->willReturnCallback(static function (string $path) use ($root): void {
            if (is_file($root . '/' . $path)) {
                unlink($root . '/' . $path);
            }
        });
        $disk->method('writeStream')->willReturnCallback(static function (string $path, $stream) use ($root, $afterWrite): void {
            $destination = $root . '/' . $path;
            if (! is_dir(dirname($destination))) {
                mkdir(dirname($destination), 0755, true);
            }

            $output = fopen($destination, 'wb');
            self::assertIsResource($output);

            try {
                stream_copy_to_stream($stream, $output);
            } finally {
                fclose($output);
            }

            if ($afterWrite !== null) {
                $afterWrite($path);
            }
        });
        $this->assetConfig->storages['alternate'] = ['disk' => $disk];

        return $disk;
    }
}
