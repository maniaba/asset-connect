<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Pending\PendingSecurityToken;

use Override;

final class CookiePendingSecurityToken extends AbstractHmacPendingSecurityToken
{
    private const string COOKIE_NAME = '__asset_pending_security_token__';

    #[Override]
    protected function initialize(): void
    {
        helper('cookie');
    }

    #[Override]
    public function generateToken(string $pendingId): string
    {
        $token = $this->retrieveToken($pendingId);

        if ($token === null) {
            $token = $this->randomStringToken();

            set_cookie([
                'name'     => $this->cookieName($pendingId),
                'value'    => $token,
                'expire'   => $this->tokenTTLSeconds,
                'httponly' => true,
                'samesite' => 'Strict',
            ]);
        }

        return $this->createTokenDigest($pendingId, $token);
    }

    #[Override]
    public function retrieveToken(string $pendingId): ?string
    {
        $token = get_cookie($this->cookieName($pendingId));

        return is_string($token) && $token !== '' ? $token : null;
    }

    #[Override]
    public function deleteToken(string $pendingId): void
    {
        delete_cookie($this->cookieName($pendingId));
    }

    private function cookieName(string $pendingId): string
    {
        return self::COOKIE_NAME . '_' . hash('sha256', $pendingId);
    }
}
