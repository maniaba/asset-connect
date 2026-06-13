<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Upgrade;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\BaseConnection;
use Maniaba\AssetConnect\AssetCollection\AssetCollection;
use Maniaba\AssetConnect\AssetCollection\SetupAssetCollection;
use Maniaba\AssetConnect\Config\Asset as AssetConfig;
use Maniaba\AssetConnect\Storage\StorageManager;
use Throwable;

final readonly class LegacyAssetPathMigrator
{
    private BaseConnection $db;
    private StorageManager $storageManager;

    public function __construct(
        private AssetConfig $config,
        ?BaseConnection $db = null,
        ?StorageManager $storageManager = null,
    ) {
        $this->db             = $db ?? db_connect($this->config->DBGroup);
        $this->storageManager = $storageManager ?? StorageManager::make($this->config);
    }

    /**
     * @param (callable(LegacyAssetPathMigrationProgress): void)|null $onProgress
     */
    public function migrate(LegacyAssetPathMigrationOptions $options, ?callable $onProgress = null): LegacyAssetPathMigrationSummary
    {
        $summary        = new LegacyAssetPathMigrationSummary();
        $summary->total = $this->countCandidates($options);

        if ($summary->total === 0) {
            return $summary;
        }

        $lastId    = 0;
        $processed = 0;
        $batchSize = max(1, $options->batchSize);

        while ($processed < $summary->total) {
            $remaining = $summary->total - $processed;
            $rows      = $this->candidateBuilder()
                ->where('id >', $lastId)
                ->orderBy('id', 'ASC')
                ->limit(min($batchSize, $remaining))
                ->get()
                ->getResultArray();

            if ($rows === []) {
                break;
            }

            foreach ($rows as $row) {
                $lastId = (int) $row['id'];
                $processed++;

                $progress = $this->migrateRow($row, $options, $processed, $summary->total);
                $summary->record($progress);

                if ($onProgress !== null) {
                    $onProgress($progress);
                }
            }
        }

        return $summary;
    }

    private function countCandidates(LegacyAssetPathMigrationOptions $options): int
    {
        $count = $this->candidateBuilder()->countAllResults();

        if ($options->limit !== null) {
            return min($count, max(0, $options->limit));
        }

        return $count;
    }

    private function candidateBuilder(): BaseBuilder
    {
        return $this->db->table($this->assetTable())
            ->select('id, collection, storage, path, file_name')
            ->groupStart()
            ->where('storage IS NULL')
            ->orWhere('storage', '')
            ->groupEnd();
    }

    /**
     * @param array<string, int|string|null> $row
     */
    private function migrateRow(array $row, LegacyAssetPathMigrationOptions $options, int $current, int $total): LegacyAssetPathMigrationProgress
    {
        $assetId    = (int) $row['id'];
        $legacyPath = (string) ($row['path'] ?? '');

        if ($legacyPath === '') {
            return $this->progress($current, $total, $assetId, LegacyAssetPathMigrationProgress::STATUS_SKIPPED, 'Asset path is empty.');
        }

        $storage      = $this->resolveStorageName($row, $options);
        $relativePath = $this->normalizeRelativePath($legacyPath);

        if ($relativePath === null) {
            return $this->progress(
                $current,
                $total,
                $assetId,
                LegacyAssetPathMigrationProgress::STATUS_FAILED,
                'Asset path must already be storage-relative.',
                $storage,
            );
        }

        $disk         = $this->storageManager->disk($storage);
        $targetExists = $disk->fileExists($relativePath);

        if ($options->dryRun) {
            return $this->progress(
                $current,
                $total,
                $assetId,
                LegacyAssetPathMigrationProgress::STATUS_DRY_RUN,
                'Would migrate asset path.',
                $storage,
                $relativePath,
            );
        }

        if (! $targetExists) {
            return $this->progress(
                $current,
                $total,
                $assetId,
                LegacyAssetPathMigrationProgress::STATUS_FAILED,
                'File does not exist on the target storage disk.',
                $storage,
                $relativePath,
            );
        }

        $updated = $this->db->table($this->assetTable())
            ->where('id', $assetId)
            ->update([
                'storage' => $storage,
                'path'    => $relativePath,
            ]);

        if (! $updated) {
            return $this->progress(
                $current,
                $total,
                $assetId,
                LegacyAssetPathMigrationProgress::STATUS_FAILED,
                'Database row could not be updated.',
                $storage,
                $relativePath,
            );
        }

        return $this->progress(
            $current,
            $total,
            $assetId,
            LegacyAssetPathMigrationProgress::STATUS_MIGRATED,
            'Database row updated.',
            $storage,
            $relativePath,
        );
    }

    /**
     * @param array<string, int|string|null> $row
     */
    private function resolveStorageName(array $row, LegacyAssetPathMigrationOptions $options): string
    {
        if ($options->storage !== null && $options->storage !== '') {
            return $options->storage;
        }

        $currentStorage = trim((string) ($row['storage'] ?? ''));
        if ($currentStorage !== '') {
            return $currentStorage;
        }

        try {
            $collectionClass = $this->config->getCollectionClassFromKey((string) ($row['collection'] ?? ''));
            $setup           = new SetupAssetCollection();
            $setup->setDefaultCollectionDefinition($collectionClass);
            $collection = AssetCollection::create($setup);

            return $collection->getStorage()
                ?? $this->storageManager->defaultDiskNameForVisibility($collection->getVisibility());
        } catch (Throwable) {
            return $this->config->defaultPublicStorage;
        }
    }

    private function normalizeRelativePath(string $path): ?string
    {
        $path = trim($path);

        if (
            $path === ''
            || str_starts_with($path, '/')
            || str_starts_with($path, '\\')
            || preg_match('#^[A-Za-z]:[\\\\/]#', $path) === 1
        ) {
            return null;
        }

        $path = preg_replace('#/+#', '/', str_replace('\\', '/', $path));

        if (! is_string($path)) {
            return null;
        }

        $path = ltrim($path, '/');

        if ($path === '' || $path === '..' || str_starts_with($path, '../') || str_contains($path, '/../')) {
            return null;
        }

        return $path;
    }

    private function assetTable(): string
    {
        return $this->config->tables['assets'];
    }

    private function progress(
        int $current,
        int $total,
        int $assetId,
        string $status,
        string $message,
        string $storage = '',
        string $relativePath = '',
    ): LegacyAssetPathMigrationProgress {
        return new LegacyAssetPathMigrationProgress(
            $current,
            $total,
            $assetId,
            $status,
            $message,
            $storage,
            $relativePath,
        );
    }
}
