<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Pending\Interfaces;

/**
 * Interface for managing pending storage operations.
 * Defines methods for generating and validating security tokens,
 * managing pending data paths, and setting default time-to-live (TTL) values.
 */
interface PendingStorageInterface
{
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
    public function getBasePendingPath(): string;

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
    public function getPendingFilePath(string $pendingId): string;

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
    public function getPendingMetadataPath(string $pendingId): string;

    /**
     * Retrieves the default time-to-live (TTL) in seconds.
     *
     * @return int The default TTL value in seconds.
     */
    public function getDefaultTTLSeconds(): int;
}
