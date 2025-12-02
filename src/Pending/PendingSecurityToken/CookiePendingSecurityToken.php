<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Pending\PendingSecurityToken;

use Override;

final class CookiePendingSecurityToken extends AbstractPendingSecurityToken
{
    private const string COOKIE_NAME = '__asset_pending_security_token_';

    #[Override]
    protected function initialize(): void
    {
        helper('cookie');
    }

    #[Override]
    public function generateToken(string $pendingId): string
    {
        $token = get_cookie(self::COOKIE_NAME);

        if ($token === null) {
            $token = $this->randomStringToken();

            // Store the token in a cookie
            set_cookie(self::COOKIE_NAME, $token, $this->tokenTTLSeconds, httpOnly: true);
        }

        return $token;
    }

    #[Override]
    public function retrieveToken(string $pendingId): ?string
    {
        return get_cookie(self::COOKIE_NAME);
    }

    #[Override]
    public function deleteToken(string $pendingId): void
    {
        delete_cookie(self::COOKIE_NAME);
    }
}
