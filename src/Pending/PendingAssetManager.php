<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Pending;

use CodeIgniter\I18n\Time;
use InvalidArgumentException;
use Maniaba\AssetConnect\Config\Asset as AssetConfig;
use Maniaba\AssetConnect\Exceptions\PendingAssetException;
use Maniaba\AssetConnect\Pending\Interfaces\PendingSecurityTokenInterface;
use Maniaba\AssetConnect\Pending\Interfaces\PendingStorageInterface;
use Random\RandomException;
use Throwable;

final class PendingAssetManager
{
    private readonly PendingStorageInterface $storage;
    private ?PendingSecurityTokenInterface $tokenProvider = null;

    private function __construct(
        ?PendingStorageInterface $storage = null,
        ?PendingSecurityTokenInterface $tokenProvider = null,
    ) {
        /** @var AssetConfig $config */
        $config = config('Asset');

        $defaultStorage = $config->pendingStorage;

        if (! is_a($defaultStorage, PendingStorageInterface::class, true)) {
            throw new InvalidArgumentException('Pending storage must be an instance of PendingStorageInterface');
        }

        $this->storage = $storage ?? new $defaultStorage();

        $defaultTokenProvider = $config->pendingSecurityToken;

        if (! is_a($defaultTokenProvider, PendingSecurityTokenInterface::class, true) && $defaultTokenProvider !== null) {
            throw new InvalidArgumentException('Pending security token provider must be an instance of PendingSecurityTokenInterface');
        }

        $this->tokenProvider = $tokenProvider ?? ($defaultTokenProvider === null ? null : new $defaultTokenProvider());
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
    public function fetchById(string $id, ?string $token = null): ?PendingAsset
    {
        $pendingAsset = $this->storage->fetchById($id);
        $ttl          = $this->storage->getDefaultTTLSeconds();

        // check created at + ttl > now
        if ($pendingAsset !== null) {
            $createdAt = $pendingAsset->created_at;
            $expiresAt = $createdAt->getTimestamp() + $ttl;
            $now       = Time::now()->getTimestamp();

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

            if ($this->tokenProvider !== null) {
                $result = $this->tokenProvider->validateToken($pendingAsset, $token);
                if (! $result) {
                    // Invalid token
                    return null;
                }
            }
        }

        return $pendingAsset;
    }

    public function deleteById(string $id, ?string $token = null): bool
    {
        $asset = $this->fetchById($id, $token);

        if ($asset === null) {
            // Asset does not exist
            return false;
        }

        $this->tokenProvider?->deleteToken($id);

        return $this->storage->deleteById($id);
    }

    /**
     * @throws RandomException
     */
    public function store(PendingAsset $pendingAsset, ?int $ttlSeconds = null): void
    {
        if ($pendingAsset->id === '') {
            // Generate and set ID
            $generateId = $this->storage->generatePendingId();
            $pendingAsset->setId($generateId);
        }

        // Set TTL
        $ttlSeconds ??= $this->storage->getDefaultTTLSeconds();
        $pendingAsset->setTTL($ttlSeconds);

        // Generate and set security token if token provider is available
        $token = $this->tokenProvider?->generateToken($pendingAsset->id) ?? null;
        $pendingAsset->setSecurityToken($token);

        $this->storage->store($pendingAsset, $pendingAsset->id);
    }

    public function cleanExpiredPendingAssets(): void
    {
        $this->storage->cleanExpiredPendingAssets();
    }
}
