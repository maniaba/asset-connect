<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Pending\Interfaces;

use Maniaba\AssetConnect\Exceptions\InvalidArgumentException;
use Maniaba\AssetConnect\Exceptions\PendingAssetException;
use Maniaba\AssetConnect\Pending\PendingAsset;

/**
 * Interface for managing pending storage operations.
 * Defines methods for generating and validating security tokens,
 * managing pending data paths, and setting default time-to-live (TTL) values.
 */
interface PendingStorageInterface
{
    public function __construct(?PendingSecurityTokenInterface $tokenProvider = null);

    /**
     * Generates a new unique pending ID as a string. UUID v4 or 32/64 hex string can be used.
     *
     * @return string The generated pending ID.
     */
    public function generatePendingId(): string;

    /**
     * Manages the creation of a pending security token.
     */
    public function pendingSecurityToken(): ?PendingSecurityTokenInterface;

    /**
     * Retrieves the default time-to-live (TTL) in seconds.
     *
     * @return int The default TTL value in seconds.
     */
    public function getDefaultTTLSeconds(): int;

    /**
     * Fetch a single pending asset by its ID.
     *
     * @param string $id ID of the pending asset to fetch.
     *
     * @return PendingAsset|null The PendingAsset object or null if not found.
     *
     * @throws InvalidArgumentException|PendingAssetException if unable to read metadata or invalid argument provided.
     */
    public function fetchById(string $id): ?PendingAsset;

    /**
     * Deletes a pending asset by its ID.
     *
     * @param string $id ID of the pending asset to delete.
     */
    public function deleteById(string $id): bool;

    /**
     * Cleans up expired pending assets.
     */
    public function cleanExpiredPendingAssets(): void;
}
