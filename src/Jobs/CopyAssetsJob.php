<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Jobs;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Events\Events;
use CodeIgniter\Queue\BaseJob;
use CodeIgniter\Queue\Config\Services;
use Maniaba\AssetConnect\Asset\Asset;
use Maniaba\AssetConnect\Asset\AssetCopier;
use Maniaba\AssetConnect\Asset\AssetPersistenceManager;
use Maniaba\AssetConnect\AssetVariants\AssetVariantsProcess;
use Maniaba\AssetConnect\Config\Asset as AssetConfig;
use Maniaba\AssetConnect\Events\AssetUpdated;
use Maniaba\AssetConnect\Exceptions\AssetException;
use Maniaba\AssetConnect\Exceptions\FileException;
use Maniaba\AssetConnect\Models\AssetModel;
use Override;
use Throwable;

/**
 * @phpstan-type CopyItem array{sourceId: int, copyId: int, variantDirectory: string}
 *
 * @property array{batch: string, items: list<CopyItem>} $data
 */
final class CopyAssetsJob extends BaseJob
{
    public const string HANDLER = 'asset_connect_copy';

    protected int $tries = 1;

    /**
     * @param list<CopyItem> $items
     */
    public static function dispatch(string $batch, array $items): void
    {
        /** @var AssetConfig $config */
        $config = config('Asset');

        $result = Services::queue()->push($config->queue['name'] ?? AssetVariantsProcess::QUEUE_NAME, self::HANDLER, [
            'batch' => $batch,
            'items' => $items,
        ]);

        if (! $result->getStatus()) {
            throw new AssetException('Failed to queue asset file copying.');
        }
    }

    #[Override]
    public function process(): void
    {
        $model   = AssetModel::init(false);
        $pending = [];

        foreach ($this->data['items'] as $item) {
            $asset = $model->withDeleted()->find($item['copyId']);
            if ($asset !== null && $asset->getInternalProperty(Asset::COPY_BATCH_PROPERTY) === $this->data['batch']) {
                $pending[] = ['asset' => $asset, 'item' => $item];
            }
        }

        if ($pending === [] || ! $this->claim($model, array_column($pending, 'asset'))) {
            return;
        }

        $copies       = [];
        $persistences = [];
        $sourceIds    = [];

        try {
            if (count($pending) !== count($this->data['items'])) {
                throw new AssetException('A queued asset copy was removed before the batch completed.');
            }

            foreach ($pending as ['asset' => $copy, 'item' => $item]) {
                if ($copy->deleted_at !== null) {
                    throw new AssetException('A queued asset copy was removed before the batch completed.');
                }

                $source = $model->find($item['sourceId']);
                if ($source === null || $source->isCopyPending()) {
                    throw FileException::forFileNotFound($copy->file_name);
                }

                $persistence    = AssetPersistenceManager::resumeQueuedCopy($copy);
                $persistences[] = $persistence;

                // The worker can use the new file for variants while readers still see a pending row.
                $copy->metadata->internal->remove(Asset::COPY_BATCH_PROPERTY);
                $persistence->stageCopy($source, $item['variantDirectory']);
                $copies[]    = $copy;
                $sourceIds[] = $source->id;
            }

            $obsoleteIds = AssetCopier::obsoleteAssetIds($model, $persistences, $sourceIds, $copies);
            $this->finalize($model, $copies, $obsoleteIds);
        } catch (Throwable $exception) {
            foreach ($persistences as $persistence) {
                $persistence->discardCopyFiles();
            }

            foreach ($pending as ['asset' => $copy]) {
                try {
                    if (! $model->delete($copy->id, true)) {
                        throw AssetException::forDatabaseError($model->errors() ?: ['Could not roll back queued asset copy.']);
                    }
                } catch (Throwable $cleanupException) {
                    log_message('error', 'Failed to roll back queued asset copy {id}: {error}', [
                        'id'    => $copy->id,
                        'error' => $cleanupException->getMessage(),
                    ]);
                }
            }

            throw $exception;
        }

        foreach ($copies as $copy) {
            Events::trigger(AssetUpdated::name(), AssetUpdated::createFromId($copy->id));
        }
    }

    /**
     * @param list<Asset> $copies
     */
    private function claim(AssetModel $model, array $copies): bool
    {
        return $this->transaction($model, function (BaseConnection $database) use ($model, $copies): bool {
            foreach ($copies as $copy) {
                $expected = json_encode($copy->metadata, JSON_THROW_ON_ERROR);
                $copy->setInternalProperty(Asset::COPY_BATCH_PROPERTY, $this->data['batch'] . ':processing');
                $updated = $model->where('metadata', $expected)->update($copy->id, [
                    'metadata' => json_encode($copy->metadata, JSON_THROW_ON_ERROR),
                ]);
                if (! $updated) {
                    throw AssetException::forDatabaseError($model->errors() ?: ['Could not claim queued asset copy.']);
                }
                if ($database->affectedRows() !== 1) {
                    return false;
                }
            }

            return true;
        });
    }

    /** @param list<Asset> $copies
     * @param list<int> $obsoleteIds
     */
    private function finalize(AssetModel $model, array $copies, array $obsoleteIds): void
    {
        $this->transaction($model, static function (BaseConnection $database) use ($model, $copies, $obsoleteIds): bool {
            foreach ($copies as $copy) {
                $updated = $model->where('deleted_at')->update($copy->id, [
                    'size'     => $copy->size,
                    'metadata' => json_encode($copy->metadata, JSON_THROW_ON_ERROR),
                ]);
                if (! $updated || $database->affectedRows() !== 1) {
                    throw AssetException::forDatabaseError($model->errors() ?: ['Could not finalize queued asset copy.']);
                }
            }

            AssetCopier::applyRetention($model, $obsoleteIds);

            return true;
        });
    }

    /**
     * @param callable(BaseConnection): bool $operation
     */
    private function transaction(AssetModel $model, callable $operation): bool
    {
        $database = $model->db;
        if (! $database instanceof BaseConnection || ! $database->transBegin()) {
            throw AssetException::forDatabaseError(['Could not begin queued copy transaction.']);
        }

        try {
            if (! $operation($database)) {
                $database->transRollback();

                return false;
            }

            if (! $database->transStatus() || ! $database->transCommit()) {
                throw AssetException::forDatabaseError(['Could not commit queued asset copies.']);
            }

            return true;
        } catch (Throwable $exception) {
            $database->transRollback();

            throw $exception;
        }
    }
}
