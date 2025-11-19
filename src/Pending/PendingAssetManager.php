<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Pending;

use Maniaba\AssetConnect\Config\Asset as AssetConfig;
use Maniaba\AssetConnect\Exceptions\InvalidArgumentException;
use Maniaba\AssetConnect\Exceptions\PendingAssetException;
use Maniaba\AssetConnect\Pending\Interfaces\PendingStorageInterface;
use Throwable;

final class PendingAssetManager
{
    private PendingStorageInterface $storage;

    private function __construct(?PendingStorageInterface $storage = null)
    {
        /** @var AssetConfig $config */
        $config = config('Asset');

        $defaultStorage = $config->pendingStorage;

        if (! is_a($defaultStorage, PendingStorageInterface::class, true)) {
            throw new \InvalidArgumentException('Pending storage must be an instance of PendingStorageInterface');
        }

        $this->storage = $storage ?? new $defaultStorage();
    }

    public static function make(?PendingStorageInterface $storage = null): PendingAssetManager
    {
        return new self($storage);
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
        $pendingAsset = $this->storage->fetchById($id);
        $ttl          = $this->storage->getDefaultTTLSeconds();

        // check created at + ttl > now
        if ($pendingAsset !== null) {
            $createdAt = $pendingAsset->created_at;
            $expiresAt = $createdAt->getTimestamp() + $ttl;
            $now       = time();

            if ($expiresAt < $now) {
                // try to delete the expired asset
                try {
                    $this->storage->deleteById($id);
                } catch (Throwable $e) {
                    // log the error but do not throw
                    log_message('critical', 'Failed to delete expired pending asset with ID {id}: {message}', [
                        'id'      => $id,
                        'message' => $e->getMessage(),
                    ]);
                }

                // Asset has expired
                return null;
            }
        }

        return $pendingAsset;
    }

    /**
     * @param string ...$ids IDs of the pending assets to fetch.
     *
     * @return list<PendingAsset> Array of PendingAsset objects or empty array if none found.
     *
     * @throws InvalidArgumentException if no IDs are provided.
     * @throws PendingAssetException    if unable to read metadata for any asset.
     */
    private function fetchByIds(string ...$ids): array
    {
        if ($ids === []) {
            throw new InvalidArgumentException('At least one ID must be provided');
        }

        $assets = [];

        foreach ($ids as $id) {
            $asset = $this->fetchById($id);
            if ($asset !== null) {
                $assets[] = $asset;
            }
        }

        return $assets;
    }

    public function deleteById(string $id): bool
    {
        return $this->storage->deleteById($id);
    }

    public function store(PendingAsset $pendingAsset, ?int $ttlSeconds = null): void
    {
        $generateId = $this->storage->generatePendingId();
        $ttlSeconds ??= $this->storage->getDefaultTTLSeconds();

        $pendingAsset->setId($generateId)->setTTL($ttlSeconds);

        $this->storage->store($pendingAsset, $generateId);
    }

    public function cleanExpiredPendingAssets(): void
    {
        $this->storage->cleanExpiredPendingAssets();
    }
}
