<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Upgrade;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\BaseConnection;
use Maniaba\AssetConnect\AssetCollection\AssetCollection;
use Maniaba\AssetConnect\AssetCollection\SetupAssetCollection;
use Maniaba\AssetConnect\Config\Asset as AssetConfig;
use Maniaba\AssetConnect\Storage\StorageManager;
use RuntimeException;
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
            ->orLike('path', '/', 'after')
            ->orLike('path', ':/', 'both')
            ->orLike('path', ':\\', 'both')
            ->orLike('path', '\\\\', 'after')
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
        $relativePath = $this->resolveRelativePath($legacyPath, $options);

        if ($relativePath === null) {
            return $this->progress(
                $current,
                $total,
                $assetId,
                LegacyAssetPathMigrationProgress::STATUS_FAILED,
                'Cannot resolve storage-relative path. Pass --from-root for this legacy path.',
                $storage,
                $legacyPath,
            );
        }

        $disk         = $this->storageManager->disk($storage);
        $sourcePath   = $this->resolveSourcePath($legacyPath, $relativePath, $options);
        $targetExists = $disk->fileExists($relativePath);

        if ($options->dryRun) {
            return $this->progress(
                $current,
                $total,
                $assetId,
                LegacyAssetPathMigrationProgress::STATUS_DRY_RUN,
                'Would migrate asset path.',
                $storage,
                $sourcePath ?? $legacyPath,
                $relativePath,
            );
        }

        if ((! $targetExists || $options->overwrite) && $sourcePath === null) {
            return $this->progress(
                $current,
                $total,
                $assetId,
                LegacyAssetPathMigrationProgress::STATUS_FAILED,
                'Source file does not exist and target file is not already present.',
                $storage,
                $legacyPath,
                $relativePath,
            );
        }

        if ($sourcePath !== null && (! $targetExists || $options->overwrite)) {
            $this->copyToStorage($sourcePath, $storage, $relativePath, $options->overwrite);
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
                $sourcePath ?? $legacyPath,
                $relativePath,
            );
        }

        if ($options->deleteSource && $sourcePath !== null) {
            $this->deleteSourceIfDifferentFromTarget($sourcePath, $storage, $relativePath);
        }

        return $this->progress(
            $current,
            $total,
            $assetId,
            LegacyAssetPathMigrationProgress::STATUS_MIGRATED,
            $targetExists && ! $options->overwrite ? 'Database row updated; target file already existed.' : 'File copied and database row updated.',
            $storage,
            $sourcePath ?? $legacyPath,
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

    private function resolveRelativePath(string $legacyPath, LegacyAssetPathMigrationOptions $options): ?string
    {
        if (! $this->isAbsolutePath($legacyPath)) {
            return $this->normalizeRelativePath($legacyPath);
        }

        foreach ($this->candidateRoots($options) as $root) {
            $relativePath = $this->relativeFromRoot($legacyPath, $root);

            if ($relativePath !== null) {
                return $this->normalizeRelativePath($relativePath);
            }
        }

        return null;
    }

    private function resolveSourcePath(string $legacyPath, string $relativePath, LegacyAssetPathMigrationOptions $options): ?string
    {
        if ($this->isFile($legacyPath)) {
            return $legacyPath;
        }

        foreach ($this->sourceRoots($options) as $root) {
            $sourcePath = $this->joinPath($root, $relativePath);

            if ($this->isFile($sourcePath)) {
                return $sourcePath;
            }
        }

        return null;
    }

    private function copyToStorage(string $sourcePath, string $storage, string $relativePath, bool $overwrite): void
    {
        $disk = $this->storageManager->disk($storage);

        if ($overwrite && $disk->fileExists($relativePath)) {
            $disk->delete($relativePath);
        }

        $stream = fopen($sourcePath, 'rb');
        if ($stream === false) {
            throw new RuntimeException("Unable to open source file '{$sourcePath}'.");
        }

        try {
            $disk->writeStream($relativePath, $stream, [
                'visibility' => $disk->visibility()->value,
            ]);
        } finally {
            fclose($stream);
        }
    }

    private function deleteSourceIfDifferentFromTarget(string $sourcePath, string $storage, string $relativePath): void
    {
        $targetPath = $this->storageManager->disk($storage)->localPath($relativePath);

        if ($targetPath !== null && realpath($sourcePath) === realpath($targetPath)) {
            return;
        }

        if (is_file($sourcePath)) {
            unlink($sourcePath);
        }
    }

    /**
     * @return list<string>
     */
    private function candidateRoots(LegacyAssetPathMigrationOptions $options): array
    {
        $roots = [];

        if ($options->fromRoot !== null && $options->fromRoot !== '') {
            $roots[] = $options->fromRoot;
        }

        if ($options->sourceRoot !== null && $options->sourceRoot !== '') {
            $roots[] = $options->sourceRoot;
        }

        foreach ($this->config->storages as $storage) {
            $root = $storage['root'] ?? null;

            if (is_string($root) && $root !== '') {
                $roots[] = $root;
            }
        }

        $roots[] = realpath(ROOTPATH . 'public') ?: ROOTPATH . 'public';
        $roots[] = WRITEPATH;

        return $this->uniquePaths($roots);
    }

    /**
     * @return list<string>
     */
    private function sourceRoots(LegacyAssetPathMigrationOptions $options): array
    {
        $roots = [];

        if ($options->sourceRoot !== null && $options->sourceRoot !== '') {
            $roots[] = $options->sourceRoot;
        }

        if ($options->fromRoot !== null && $options->fromRoot !== '') {
            $roots[] = $options->fromRoot;
        }

        return $this->uniquePaths($roots);
    }

    /**
     * @param list<string> $paths
     *
     * @return list<string>
     */
    private function uniquePaths(array $paths): array
    {
        $normalized = [];

        foreach ($paths as $path) {
            $path = rtrim(str_replace('\\', '/', $path), '/');

            if ($path !== '') {
                $normalized[$path] = $path;
            }
        }

        return array_values($normalized);
    }

    private function relativeFromRoot(string $path, string $root): ?string
    {
        $path = str_replace('\\', '/', $path);
        $root = rtrim(str_replace('\\', '/', $root), '/');

        if ($path === $root) {
            return '';
        }

        if (! str_starts_with($path, $root . '/')) {
            return null;
        }

        return substr($path, strlen($root) + 1);
    }

    private function normalizeRelativePath(string $path): ?string
    {
        $path = preg_replace('#/+#', '/', str_replace('\\', '/', trim($path)));

        if (! is_string($path)) {
            return null;
        }

        $path = ltrim($path, '/');

        if ($path === '' || $path === '..' || str_starts_with($path, '../') || str_contains($path, '/../')) {
            return null;
        }

        return $path;
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/')
            || preg_match('#^[A-Za-z]:[\\\\/]#', $path) === 1
            || str_starts_with($path, '\\\\');
    }

    private function isFile(string $path): bool
    {
        return $path !== '' && is_file($path);
    }

    private function joinPath(string $root, string $relativePath): string
    {
        return rtrim($root, DIRECTORY_SEPARATOR . '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
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
        string $sourcePath = '',
        string $relativePath = '',
    ): LegacyAssetPathMigrationProgress {
        return new LegacyAssetPathMigrationProgress(
            $current,
            $total,
            $assetId,
            $status,
            $message,
            $storage,
            $sourcePath,
            $relativePath,
        );
    }
}
