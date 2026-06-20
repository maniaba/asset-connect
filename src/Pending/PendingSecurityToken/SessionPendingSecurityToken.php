<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Pending\PendingSecurityToken;

use CodeIgniter\Session\Session;
use InvalidArgumentException;
use Override;

final class SessionPendingSecurityToken extends AbstractHmacPendingSecurityToken
{
    public const string SESSION_KEY = '__asset_pending_security_token__';

    private Session $session;

    #[Override]
    public function generateToken(string $pendingId): string
    {
        $token = $this->retrieveToken($pendingId);

        if ($token === null) {
            $token = $this->randomStringToken();

            $this->session->setTempdata($this->sessionKey($pendingId), $token, $this->tokenTTLSeconds);
        }

        return $this->createTokenDigest($pendingId, $token);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function retrieveToken(string $pendingId): ?string
    {
        $token = $this->session->getTempdata($this->sessionKey($pendingId));

        return is_string($token) && $token !== '' ? $token : null;
    }

    #[Override]
    public function deleteToken(string $pendingId): void
    {
        $this->session->removeTempdata($this->sessionKey($pendingId));
    }

    #[Override]
    protected function initialize(): void
    {
        /** @var Session|null $session */
        $session = service('session');

        if ($session === null) {
            throw new InvalidArgumentException('Session service is not available. Ensure that sessions are properly configured.');
        }

        $this->session = $session;
    }

    private function sessionKey(string $pendingId): string
    {
        return self::SESSION_KEY . '_' . hash('sha256', $pendingId);
    }
}
