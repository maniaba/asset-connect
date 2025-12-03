<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Pending\Interfaces;

use Maniaba\AssetConnect\Pending\PendingAsset;

interface PendingSecurityTokenInterface
{
    /**
     * Generate a new security token for a pending asset.
     */
    public function generateToken(string $pendingId): string;

    /**
     * Retrieve the token from user input based on the chosen strategy
     * (session, cookie, POST/GET, header, etc.)
     */
    public function retrieveToken(string $pendingId): ?string;

    /**
     * Validate user-provided token against the stored one.
     */
    public function validateToken(PendingAsset $pendingAsset, ?string $tokenProvided = null): bool;

    /**
     * Delete the stored token for the given pending ID.
     */
    public function deleteToken(string $pendingId): void;
}
