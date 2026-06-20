<?php

declare(strict_types=1);

namespace Tests\Support\Pending;

use Maniaba\AssetConnect\Pending\PendingSecurityToken\AbstractPendingSecurityToken;
use Override;

final class PlainPendingSecurityToken extends AbstractPendingSecurityToken
{
    public ?string $storedToken = null;
    public bool $initialized    = false;

    #[Override]
    public function generateToken(string $pendingId): string
    {
        unset($pendingId);

        return $this->randomStringToken();
    }

    #[Override]
    public function retrieveToken(string $pendingId): ?string
    {
        unset($pendingId);

        return $this->storedToken;
    }

    #[Override]
    public function deleteToken(string $pendingId): void
    {
        unset($pendingId);
    }

    #[Override]
    protected function initialize(): void
    {
        $this->initialized = true;
    }
}
