<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Pending;

use CodeIgniter\I18n\Time;
use ErrorException;
use Maniaba\AssetConnect\Exceptions\PendingAssetException;
use Maniaba\AssetConnect\Pending\Interfaces\PendingStorageInterface;
use Override;
use Random\RandomException;

class DefaultPendingStorage implements PendingStorageInterface
{
    /**
     * @throws PendingAssetException|RandomException if unable to generate unique ID
     */
    #[Override]
    public function generatePendingId(): string
    {
        $randomId = bin2hex(random_bytes(16));

        $limitTries = 5;
        $tries      = 0;

        while (is_dir($this->getBasePendingPath() . $randomId . DIRECTORY_SEPARATOR)) {
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
        $filePath     = $this->getPendingRawFilePath($id);
        $metadataPath = $this->getPendingMetadataFilePath($id);

        if (! file_exists($filePath) || ! file_exists($metadataPath)) {
            return null;
        }

        $metadataJson = file_get_contents($metadataPath);
        if ($metadataJson === false) {
            // Unable to read metadata file
            throw PendingAssetException::forUnableToReadMetadata($id);
        }

        $metadata = json_decode($metadataJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Invalid JSON in metadata file
            throw PendingAssetException::forUnableToReadMetadata($id);
        }

        return PendingAsset::createFromFile($filePath, $metadata);
    }

    #[Override]
    public function deleteById(string $id): bool
    {
        $basePath = $this->getBasePendingPath() . $id . DIRECTORY_SEPARATOR;

        if (! is_dir($basePath)) {
            return true; // Directory does not exist, nothing to delete
        }

        // Recursively delete the directory and its contents
        $result = delete_files($basePath, true, true, true);

        // Finally, remove the base directory itself
        @rmdir($basePath);

        return $result;
    }

    /**
     * Retrieves the base path for pending operations or resources.
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
        return WRITEPATH . 'assets_pending' . DIRECTORY_SEPARATOR;
    }

    /**
     * Retrieves the file path associated with a pending operation using the provided pending ID.
     *
     * Example:
     *   base: pending/
     *   pendingId: abc123
     * Resulting path:
     *   pending/abc123/data.json
     *
     * @param string $pendingId The unique identifier for the pending operation.
     *
     * @return string The file path corresponding to the provided pending ID.
     */
    private function getPendingRawFilePath(string $pendingId): string
    {
        return $this->getBasePendingPath() . $pendingId . DIRECTORY_SEPARATOR . 'file';
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
        return $this->getBasePendingPath() . $pendingId . DIRECTORY_SEPARATOR . 'metadata.json';
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

        // Set the ID on the asset
        $asset->setId($id);

        // store file on  getPendingFilePath
        $storeFilePath = $this->getPendingRawFilePath($id);
        $directory     = dirname($storeFilePath);

        // Ensure the directory exists
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Store metadata file
        $this->storeMetadataFile($asset);

        // if file already exists at path, return because we do not want to overwrite
        if (file_exists($storeFilePath)) {
            // Delete the temporary file if it was created (since we're not using it for update)
            @unlink($asset->file->getRealPath());

            return;
        }

        // Move or copy the file to the pending file path to $filePath
        $success = copy($asset->file->getRealPath(), $storeFilePath);
        if (! $success) {
            throw PendingAssetException::forUnableToStorePendingAsset($id, 'Failed to copy file to pending storage.');
        }
        // Delete the temporary file if it was created
        @unlink($asset->file->getRealPath());
    }

    private function storeMetadataFile(PendingAsset $asset): void
    {
        $metadataPath = $this->getPendingMetadataFilePath($asset->id);

        if (file_exists($metadataPath)) {
            // unlink an existing file to update it
            @unlink($metadataPath);
        }

        // Store metadata as JSON
        $metadataJson = json_encode($asset);

        try {
            $result = file_put_contents($metadataPath, $metadataJson);
            if ($result === false) {
                throw PendingAssetException::forUnableToStorePendingAsset($asset->id, 'Failed to write metadata file.');
            }
        } catch (ErrorException $e) {
            throw PendingAssetException::forUnableToStorePendingAsset($asset->id, 'Failed to write metadata file: ' . $e->getMessage());
        }
    }

    #[Override]
    public function cleanExpiredPendingAssets(): void
    {
        $basePath   = $this->getBasePendingPath();
        $ttlSeconds = $this->getDefaultTTLSeconds();
        $now        = Time::now()->getTimestamp();

        if (! is_dir($basePath)) {
            return; // No pending assets to clean
        }

        helper('filesystem');

        $directories = directory_map($basePath, 1);
        if ($directories === []) {
            return; // No directories found
        }

        foreach ($directories as $directory) {
            $pendingId    = rtrim((string) $directory, DIRECTORY_SEPARATOR);
            $metadataPath = $this->getPendingMetadataFilePath($pendingId);

            if (! is_file($metadataPath)) {
                continue; // No metadata file, skip
            }

            $metadataJson = file_get_contents($metadataPath);
            if ($metadataJson === false) {
                continue; // Unable to read metadata, skip
            }

            $metadata = json_decode($metadataJson, true);
            if (json_last_error() !== JSON_ERROR_NONE || ! isset($metadata['created_at'])) {
                continue; // Invalid metadata, skip
            }

            $createdAt = strtotime((string) $metadata['created_at']);
            if ($createdAt === false) {
                continue; // Invalid created_at format, skip
            }

            if (($createdAt + $ttlSeconds) < $now) {
                // Asset has expired, delete it
                $this->deleteById($pendingId);
            }
        }
    }
}
