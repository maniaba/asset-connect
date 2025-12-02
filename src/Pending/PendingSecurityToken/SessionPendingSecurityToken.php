<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Pending\PendingSecurityToken;

use CodeIgniter\Session\Session;
use InvalidArgumentException;
use Override;

final class SessionPendingSecurityToken extends AbstractPendingSecurityToken
{
    private const string SESSION_KEY_PREFIX = '__pending_asset_security_token_';

    private Session $session;

    #[Override]
    public function generateToken(string $pendingId): string
    {
        $token = $this->randomStringToken();

        // Store the token in the session or other storage mechanism
        $this->session->setTempdata($this->sessionKey($pendingId), $token, $this->tokenTTLSeconds);

        return $token;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function retrieveToken(string $pendingId): ?string
    {
        return $this->session->getTempdata($this->sessionKey($pendingId));
    }

    private function sessionKey(string $pendingId): string
    {
        return self::SESSION_KEY_PREFIX . $pendingId;
    }

    #[Override]
    public function deleteToken(string $pendingId): void
    {
        $this->session->removeTempdata($this->sessionKey($pendingId));
    }

    #[Override]
    protected function initialize(): void
    {
        /**
         * @var Session|null $session
         */
        $session = service('session');

        if ($session === null) {
            throw new InvalidArgumentException('Session service is not available. Ensure that sessions are properly configured.');
        }

        $this->session = $session;
    }
}
