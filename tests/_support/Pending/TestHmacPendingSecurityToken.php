<?php

declare(strict_types=1);

namespace Tests\Support\Pending;

use Maniaba\AssetConnect\Pending\PendingSecurityToken\AbstractHmacPendingSecurityToken;
use Override;

final class TestHmacPendingSecurityToken extends AbstractHmacPendingSecurityToken
{
    public ?string $ownerProof = null;

    #[Override]
    public function generateToken(string $pendingId): string
    {
        return $this->createTokenDigest($pendingId, (string) $this->ownerProof);
    }

    #[Override]
    public function retrieveToken(string $pendingId): ?string
    {
        unset($pendingId);

        return $this->ownerProof;
    }

    #[Override]
    public function deleteToken(string $pendingId): void
    {
        unset($pendingId);
    }

    #[Override]
    protected function initialize(): void
    {
    }
}
