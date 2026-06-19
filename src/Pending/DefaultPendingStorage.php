<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Pending;

use CodeIgniter\I18n\Time;
use InvalidArgumentException;
use Maniaba\AssetConnect\Config\Asset as AssetConfig;
use Maniaba\AssetConnect\Enums\AssetVisibility;
use Maniaba\AssetConnect\Exceptions\PendingAssetException;
use Maniaba\AssetConnect\Pending\Interfaces\PendingStorageInterface;
use Maniaba\AssetConnect\Storage\Interfaces\StorageDiskInterface;
use Maniaba\AssetConnect\Storage\StorageManager;
use Override;
use Random\RandomException;
use Throwable;

class DefaultPendingStorage implements PendingStorageInterface
{
    private readonly StorageDiskInterface $disk;
    private readonly string $basePath;

    public function __construct(?StorageDiskInterface $disk = null, ?string $basePath = null)
    {
        /** @var AssetConfig $config */
        $config = config('Asset');

        $pendingDiskName = $config->pendingStorageDisk ?? $config->defaultProtectedStorage;

        $this->disk = $disk ?? StorageManager::make($config)->disk($pendingDiskName);
        if ($this->disk->visibility() !== AssetVisibility::PROTECTED) {
            throw new InvalidArgumentException('Pending storage disk must be protected.');
        }

        $this->basePath = $this->normalizeBasePath($basePath ?? $config->pendingStoragePrefix);
    }

    /**
     * @throws PendingAssetException|RandomException if unable to generate unique ID
     */
    #[Override]
    public function generatePendingId(): string
    {
        $randomId = bin2hex(random_bytes(16));

        $limitTries = 5;
        $tries      = 0;

        while ($this->pendingAssetExists($randomId)) {
            $randomId = bin2hex(random_bytes(16));
            $tries++;
            if ($tries >= $limitTries) {
                throw new PendingAssetException('Unable to generate unique pending ID after ' . $limitTries . ' attempts.');
            }
        }

        return $randomId;
    }

    /**
     * Fetch a single pending asset by its ID.
     *
     * @param string $id ID of the pending asset to fetch.
     *
     * @return PendingAsset|null The PendingAsset object or null if not found.
     *
     * @throws PendingAssetException if unable to read metadata.
     */
    #[Override]
    public function fetchById(string $id): ?PendingAsset
    {
        $this->assertValidPendingId($id);

        $filePath     = $this->getPendingRawFilePath($id);
        $metadataPath = $this->getPendingMetadataFilePath($id);

        if (! $this->disk->fileExists($filePath) || ! $this->disk->fileExists($metadataPath)) {
            return null;
        }

        try {
            $metadataJson = $this->disk->read($metadataPath);
        } catch (Throwable) {
            throw PendingAssetException::forUnableToReadMetadata($id);
        }

        $metadata = json_decode($metadataJson, true);
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($metadata)) {
            throw PendingAssetException::forUnableToReadMetadata($id);
        }

        $temporaryPath = $this->copyPendingFileToTemporarySource($filePath, $id);

        try {
            return PendingAsset::createFromFile($temporaryPath, $this->normalizeMetadata($metadata));
        } catch (Throwable $exception) {
            @unlink($temporaryPath);

            if ($exception instanceof PendingAssetException) {
                throw $exception;
            }

            throw PendingAssetException::forUnableToReadMetadata($id);
        }
    }

    #[Override]
    public function deleteById(string $id): bool
    {
        $this->assertValidPendingId($id);

        if (! $this->pendingAssetExists($id)) {
            return true;
        }

        try {
            foreach ($this->getKnownPendingFilePaths($id) as $path) {
                if ($this->disk->fileExists($path)) {
                    $this->disk->delete($path);
                }
            }

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Retrieves the base path for pending operations or resources within the configured storage disk.
     *  Example:
     *    base: pending/
     *    pendingId: abc123
     *
     *  Resulting path:
     *    pending/abc123/
     *
     * @return string The base path designated for pending items.
     */
    private function getBasePendingPath(): string
    {
        return $this->basePath;
    }

    /**
     * Retrieves the file path associated with a pending operation using the provided pending ID.
     *
     * Example:
     *   base: pending/
     *   pendingId: abc123
     * Resulting path:
     *   pending/abc123/file
     *
     * @param string $pendingId The unique identifier for the pending operation.
     *
     * @return string The file path corresponding to the provided pending ID.
     */
    private function getPendingRawFilePath(string $pendingId): string
    {
        return $this->getPendingDirectoryPath($pendingId) . '/file';
    }

    /**
     * Retrieves the metadata path associated with a pending operation using the provided pending ID.
     *
     * Example:
     *   base: pending/
     *   pendingId: abc123
     * Resulting path:
     *   pending/abc123/metadata.json
     *
     * @param string $pendingId The unique identifier for the pending operation.
     *
     * @return string The metadata path corresponding to the provided pending ID.
     */
    private function getPendingMetadataFilePath(string $pendingId): string
    {
        return $this->getPendingDirectoryPath($pendingId) . '/metadata.json';
    }

    private function getPendingDirectoryPath(string $pendingId): string
    {
        return $this->getBasePendingPath() . '/' . $pendingId;
    }

    #[Override]
    public function getDefaultTTLSeconds(): int
    {
        return 86400; // 24 hours
    }

    /**
     * Stores a pending asset. If an ID is provided, it will be updated only metadata.
     *
     * @param PendingAsset $asset The pending asset to be stored.
     *
     * @throws PendingAssetException|RandomException if unable to store the asset.
     */
    #[Override]
    public function store(PendingAsset $asset, ?string $id = null): void
    {
        $id ??= $this->generatePendingId();
        $this->assertValidPendingId($id);

        // Set the ID on the asset
        $asset->setId($id);

        $sourcePath = $asset->file->getRealPath();
        if (! is_string($sourcePath) || ! is_file($sourcePath)) {
            throw PendingAssetException::forUnableToStorePendingAsset($id, 'Pending source file is not readable.');
        }

        $this->storeMetadataFile($asset);

        $storeFilePath = $this->getPendingRawFilePath($id);

        // if file already exists at path, return because we do not want to overwrite
        if ($this->disk->fileExists($storeFilePath)) {
            @unlink($sourcePath);

            return;
        }

        $stream = fopen($sourcePath, 'rb');
        if ($stream === false) {
            throw PendingAssetException::forUnableToStorePendingAsset($id, 'Failed to open source file stream.');
        }

        try {
            $this->disk->writeStream($storeFilePath, $stream, [
                'visibility' => AssetVisibility::PROTECTED,
            ]);
        } catch (Throwable $exception) {
            throw PendingAssetException::forUnableToStorePendingAsset($id, 'Failed to write file to pending storage: ' . $exception->getMessage());
        } finally {
            fclose($stream);
        }

        @unlink($sourcePath);
    }

    private function storeMetadataFile(PendingAsset $asset): void
    {
        $this->assertValidPendingId($asset->id);

        $metadataPath = $this->getPendingMetadataFilePath($asset->id);
        $metadataJson = json_encode($asset);

        if (! is_string($metadataJson)) {
            throw PendingAssetException::forUnableToStorePendingAsset($asset->id, 'Failed to encode metadata.');
        }

        try {
            $this->disk->delete($metadataPath);
            $this->disk->write($metadataPath, $metadataJson, [
                'visibility' => AssetVisibility::PROTECTED,
            ]);
        } catch (Throwable $e) {
            throw PendingAssetException::forUnableToStorePendingAsset($asset->id, 'Failed to write metadata file: ' . $e->getMessage());
        }
    }

    #[Override]
    public function cleanExpiredPendingAssets(): void
    {
        // Default storage avoids listing remote buckets. Expiration should be
        // handled by storage lifecycle rules or by deleting known pending IDs.
    }

    private function copyPendingFileToTemporarySource(string $filePath, string $id): string
    {
        $temporaryPath = tempnam(sys_get_temp_dir(), 'pending_asset_');
        if ($temporaryPath === false) {
            throw PendingAssetException::forUnableToReadMetadata($id);
        }

        $source = null;
        $target = null;

        try {
            $source = $this->disk->readStream($filePath);
            if (! is_resource($source)) {
                throw PendingAssetException::forUnableToReadMetadata($id);
            }

            $target = fopen($temporaryPath, 'wb');
            if ($target === false || stream_copy_to_stream($source, $target) === false) {
                throw PendingAssetException::forUnableToReadMetadata($id);
            }
        } catch (Throwable $exception) {
            @unlink($temporaryPath);

            if ($exception instanceof PendingAssetException) {
                throw $exception;
            }

            throw PendingAssetException::forUnableToReadMetadata($id);
        } finally {
            if (is_resource($target)) {
                fclose($target);
            }

            if (is_resource($source)) {
                fclose($source);
            }
        }

        register_shutdown_function(static function () use ($temporaryPath): void {
            if (is_file($temporaryPath)) {
                @unlink($temporaryPath);
            }
        });

        return $temporaryPath;
    }

    /**
     * @param array<string, mixed> $metadata
     *
     * @return array<string, mixed>
     */
    private function normalizeMetadata(array $metadata): array
    {
        foreach (['created_at', 'updated_at'] as $key) {
            $value     = $metadata[$key] ?? null;
            $timestamp = null;

            if ($value instanceof Time) {
                $timestamp = $value->getTimestamp();
            } elseif (is_array($value)) {
                $date = $value['date'] ?? null;
                if (is_string($date)) {
                    $timestamp = $this->metadataTimeStringToTimestamp($date);
                }
            } elseif (is_string($value)) {
                $timestamp = $this->metadataTimeStringToTimestamp($value);
            }

            if ($timestamp !== null) {
                $metadata[$key] = Time::createFromTimestamp($timestamp);
            }
        }

        return $metadata;
    }

    private function metadataTimeStringToTimestamp(string $value): ?int
    {
        if ($value === '') {
            return null;
        }

        try {
            return Time::parse($value)->getTimestamp();
        } catch (Throwable) {
            return null;
        }
    }

    private function pendingAssetExists(string $pendingId): bool
    {
        foreach ($this->getKnownPendingFilePaths($pendingId) as $path) {
            if ($this->disk->fileExists($path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function getKnownPendingFilePaths(string $pendingId): array
    {
        return [
            $this->getPendingRawFilePath($pendingId),
            $this->getPendingMetadataFilePath($pendingId),
        ];
    }

    private function normalizeBasePath(string $basePath): string
    {
        $basePath = trim(str_replace('\\', '/', $basePath), '/');

        if ($basePath === '') {
            throw new InvalidArgumentException('Pending storage prefix must not be empty.');
        }

        return $basePath;
    }

    private function assertValidPendingId(string $pendingId): void
    {
        if (preg_match('/\A[A-Za-z0-9_-]{1,128}\z/', $pendingId) === 1) {
            return;
        }

        throw new InvalidArgumentException('Pending asset ID contains invalid characters.');
    }
}
