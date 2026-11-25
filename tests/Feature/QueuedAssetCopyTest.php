<?php

declare(strict_types=1);

namespace Tests\Feature;

use CodeIgniter\Events\Events;
use CodeIgniter\I18n\Time;
use CodeIgniter\Queue\Config\Services as QueueServices;
use CodeIgniter\Queue\Interfaces\QueueInterface;
use CodeIgniter\Queue\QueuePushResult;
use Maniaba\AssetConnect\Asset\Asset;
use Maniaba\AssetConnect\AssetCollection\DefaultAssetCollection;
use Maniaba\AssetConnect\AssetVariants\AssetVariant;
use Maniaba\AssetConnect\Config\Registrar;
use Maniaba\AssetConnect\Events\VariantCreated;
use Maniaba\AssetConnect\Exceptions\AssetException;
use Maniaba\AssetConnect\Exceptions\FileException;
use Maniaba\AssetConnect\Exceptions\PageException;
use Maniaba\AssetConnect\Jobs\AssetConnectJob;
use Maniaba\AssetConnect\Jobs\CopyAssetsJob;
use Maniaba\AssetConnect\Models\AssetModel;
use Maniaba\AssetConnect\Services\AssetAccessService;
use Tests\Support\AssetCollections\FakeDocumentCollection;
use Tests\Support\AssetCollections\ImmediateVariantTestAssetCollection;
use Tests\Support\AssetCollections\SingleFileTestAssetCollection;
use Tests\Support\AssetConnectFeatureTestCase;

/**
 * @internal
 */
final class QueuedAssetCopyTest extends AssetConnectFeatureTestCase
{
    public function testMetadataIsAvailableBeforeTheWorkerCopiesTheFileAndVariants(): void
    {
        $source = $this->createFakeEntity();
        $target = $this->createFakeEntity();
        $asset  = $source->addAssetFromString('original', 'queued-copy.txt')
            ->withCustomProperty('category', 'report')
            ->setOrder(3)
            ->toAssetCollection();

        $data = [];
        $this->captureJob($data);

        try {
            $copy = $asset->copyToEntityUsingQueue($target, collection: ImmediateVariantTestAssetCollection::class);
        } finally {
            QueueServices::resetSingle('queue');
        }

        $this->assertTrue(AssetModel::init(false)->find($copy->id)?->isCopyPending());
        $this->assertSame($copy->id, $target->getFirstAsset()?->id);
        $this->assertSame('report', $copy->getCustomProperty('category'));
        $this->assertSame(3, $copy->order);
        $this->assertTrue($copy->isCopyPending());
        $this->assertSame('', $copy->getUrl(forceDownload: true));
        $this->assertSame('', $copy->getTemporaryUrl(Time::now()->addHours(1), forceDownload: true));
        $this->assertNull($copy->local_path);
        $this->assertFalse($copy->getStorageDisk()->fileExists($copy->path));
        $this->assertSame(CopyAssetsJob::class, Registrar::Queue()['jobHandlers'][CopyAssetsJob::HANDLER]);

        try {
            (new AssetAccessService())->handleAssetRequest($copy->id);
            $this->fail('Expected the pending copy to be unavailable.');
        } catch (PageException $exception) {
            $this->assertSame(404, $exception->getCode());
        }

        Events::on(VariantCreated::name(), static function () use ($data): void {
            (new CopyAssetsJob($data))->process();
        });

        try {
            (new CopyAssetsJob($data))->process();
        } finally {
            Events::removeAllListeners(VariantCreated::name());
        }
        (new CopyAssetsJob($data))->process();

        $ready = AssetModel::init(false)->find($copy->id);
        $this->assertInstanceOf(Asset::class, $ready);
        $this->assertFalse($ready->isCopyPending());
        $this->assertSame($copy->path, $ready->path);
        $this->assertNotSame(dirname($asset->path), dirname($ready->path));
        $this->assertAssetFileContains($ready, 'original');
        $this->assertAssetFileContains($asset, 'original');

        $variant = $ready->metadata->assetVariant->getAssetVariant('preview');
        $this->assertInstanceOf(AssetVariant::class, $variant);
        $this->assertTrue($variant->processed);
        $this->assertSame(dirname($ready->path) . '/variants/queued-copy-preview.txt', $variant->path);
        $this->assertSame('preview for queued-copy.txt', $variant->getStorageDisk()->read($variant->path));
    }

    public function testQueuesTheSelectedCollectionAsOneBatchOnTheRequestedDisk(): void
    {
        $source                                 = $this->createFakeEntity();
        $target                                 = $this->createFakeEntity();
        $this->assetConfig->storages['archive'] = [
            'driver' => 'local',
            'root'   => $this->protectedStorageRoot,
        ];
        $source->addAssetFromString('first', 'first.txt')->toAssetCollection(DefaultAssetCollection::class);
        $source->addAssetFromString('second', 'second.txt')->setOrder(2)->toAssetCollection(DefaultAssetCollection::class);
        $source->addAssetFromString('excluded', 'excluded.txt')->toAssetCollection();

        $data = [];
        $this->captureJob($data);

        try {
            $copies = $source->copyAssetsToUsingQueue($target, collection: DefaultAssetCollection::class, storage: 'archive');
        } finally {
            QueueServices::resetSingle('queue');
        }

        $this->assertCount(2, $copies);
        $this->assertSame(array_column($copies, 'id'), array_column($data['items'], 'copyId'));
        $this->assertSame(['archive', 'archive'], array_column($copies, 'storage'));
        $this->assertFalse($copies[0]->getStorageDisk()->fileExists($copies[0]->path));
        $this->assertFalse($copies[1]->getStorageDisk()->fileExists($copies[1]->path));

        (new CopyAssetsJob($data))->process();
        $this->assertAssetFileContains($copies[0], 'first');
        $this->assertAssetFileContains($copies[1], 'second');
    }

    public function testFailedWorkerRollsBackTheBatchAndKeepsTheOriginals(): void
    {
        $source = $this->createFakeEntity();
        $target = $this->createFakeEntity();
        $first  = $source->addAssetFromString('first', 'first.txt')->toAssetCollection(ImmediateVariantTestAssetCollection::class);
        $second = $source->addAssetFromString('second', 'second.txt')->setOrder(2)->toAssetCollection(ImmediateVariantTestAssetCollection::class);

        $data = [];
        $this->captureJob($data);

        try {
            $copies = $source->copyAssetsToUsingQueue($target);
        } finally {
            QueueServices::resetSingle('queue');
        }

        $second->getStorageDisk()->delete($second->path);

        try {
            (new CopyAssetsJob($data))->process();
            $this->fail('Expected the missing source to fail the batch.');
        } catch (FileException $exception) {
            $this->assertSame(404, $exception->getCode());
        }

        foreach ($copies as $copy) {
            $this->assertNull(AssetModel::init(false)->withDeleted()->find($copy->id));
            $this->assertFalse($copy->getStorageDisk()->fileExists($copy->path));
        }

        $this->assertFalse($copies[0]->getStorageDisk()->fileExists(dirname($copies[0]->path) . '/variants/first-preview.txt'));
        $this->assertAssetFileContains($first, 'first');
    }

    public function testRejectedJobRemovesPendingRows(): void
    {
        $source = $this->createFakeEntity();
        $target = $this->createFakeEntity();
        $asset  = $source->addAssetFromString('original', 'queue-unavailable.txt')->toAssetCollection();
        $queue  = $this->createMock(QueueInterface::class);
        $queue->expects($this->once())->method('push')->willReturn(QueuePushResult::failure('Unavailable'));
        QueueServices::injectMock('queue', $queue);

        try {
            $asset->copyToEntityUsingQueue($target, collection: DefaultAssetCollection::class);
            $this->fail('Expected queue submission to fail.');
        } catch (AssetException $exception) {
            $this->assertSame(['Failed to queue asset file copying.'], $exception->errors);
        } finally {
            QueueServices::resetSingle('queue');
        }

        $this->assertCount(1, AssetModel::init(false)->withDeleted()->findAll());
        $this->assertEntityAssetCount($target, 0);
        $this->assertAssetFileContains($asset, 'original');
    }

    public function testCancellingPendingCopyDoesNotDeleteAnExistingStoragePath(): void
    {
        $source = $this->createFakeEntity();
        $owner  = $this->createFakeEntity();
        $target = $this->createFakeEntity();
        $asset  = $source->addAssetFromString('original', 'collision.txt')->toAssetCollection(DefaultAssetCollection::class);
        $other  = $owner->addAssetFromString('existing', 'collision.txt')->toAssetCollection();

        $data = [];
        $this->captureJob($data);

        try {
            $copy = $asset->copyToEntityUsingQueue($target, collection: FakeDocumentCollection::class);
        } finally {
            QueueServices::resetSingle('queue');
        }

        $this->assertSame($other->path, $copy->path);
        $this->assertTrue($target->deleteAssets());
        (new AssetConnectJob([]))->cleanGarbage();
        (new CopyAssetsJob($data))->process();

        $this->assertNull(AssetModel::init(false)->withDeleted()->find($copy->id));
        $this->assertAssetFileContains($other, 'existing');
    }

    public function testRetentionRunsOnlyAfterQueuedFilesAreReady(): void
    {
        $source = $this->createFakeEntity();
        $target = $this->createFakeEntity();
        $old    = $target->addAssetFromString('existing', 'old.txt')->toAssetCollection(SingleFileTestAssetCollection::class);
        $asset  = $source->addAssetFromString('original', 'source.txt')->toAssetCollection(SingleFileTestAssetCollection::class);

        $data = [];
        $this->captureJob($data);

        try {
            $copy = $asset->copyToEntityUsingQueue($target);
        } finally {
            QueueServices::resetSingle('queue');
        }

        $this->assertInstanceOf(Asset::class, AssetModel::init(false)->find($old->id));
        (new CopyAssetsJob($data))->process();
        $this->assertAssetWasSoftDeleted($old);
        $this->assertAssetFileContains($copy, 'original');
    }

    private function captureJob(array &$data): void
    {
        $queue = $this->createMock(QueueInterface::class);
        $queue->expects($this->once())->method('push')
            ->with('asset_queue', CopyAssetsJob::HANDLER, $this->isArray())
            ->willReturnCallback(static function (string $queue, string $handler, array $payload) use (&$data): QueuePushResult {
                $data = $payload;

                return QueuePushResult::success(1);
            });
        QueueServices::injectMock('queue', $queue);
    }
}
