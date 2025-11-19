<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Pending;

use CodeIgniter\I18n\Time;
use Maniaba\AssetConnect\Exceptions\PendingAssetException;
use Maniaba\AssetConnect\Pending\Interfaces\PendingSecurityTokenInterface;
use Maniaba\AssetConnect\Pending\PendingSecurityToken\SessionPendingSecurityToken;
use Random\RandomException;

class DefaultPendingStorage implements Interfaces\PendingStorageInterface
{
    protected PendingSecurityTokenInterface $tokenProvider;

    public function __construct(?PendingSecurityTokenInterface $tokenProvider = null)
    {
        $this->tokenProvider = $tokenProvider ?? new SessionPendingSecurityToken($this->getDefaultTTLSeconds());
    }

    /**
     * @throws PendingAssetException|RandomException if unable to generate unique ID
     */
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

    public function pendingSecurityToken(): ?PendingSecurityTokenInterface
    {
        return $this->tokenProvider;
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

    public function getDefaultTTLSeconds(): int
    {
        return 86400; // 24 hours
    }

    /**
     * @param PendingAsset $asset The pending asset to be stored.
     */
    public function store(PendingAsset $asset, ?string $id = null): void
    {
        $id ??= $this->generatePendingId();

        // store file on  getPendingFilePath
        $storeFilePath = $this->getPendingRawFilePath($id);
        $directory     = dirname($storeFilePath);
        $metadataPath  = $this->getPendingMetadataFilePath($id);

        // Ensure the directory exists
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Move or copy the file to the pending file path to $filePath
        $success = copy($asset->file->getRealPath(), $storeFilePath);
        if (! $success) {
            throw PendingAssetException::forUnableToStorePendingAsset($id, 'Failed to copy file to pending storage.');
        }
        // Delete the temporary file if it was created
        @unlink($asset->file->getRealPath());

        // Store metadata as JSON
        $metadataJson = json_encode($asset);

        if (file_put_contents($metadataPath, $metadataJson) === false) {
            throw PendingAssetException::forUnableToStorePendingAsset($id, 'Failed to write metadata file.');
        }
    }

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
            $pendingId    = rtrim($directory, DIRECTORY_SEPARATOR);
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

            $createdAt = strtotime($metadata['created_at']);
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
