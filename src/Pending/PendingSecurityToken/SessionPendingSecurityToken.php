<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Pending\PendingSecurityToken;

use CodeIgniter\Session\Session;
use InvalidArgumentException;
use Override;

final class SessionPendingSecurityToken extends AbstractPendingSecurityToken
{
    public const string SESSION_KEY = '__asset_pending_security_token__';

    private Session $session;

    #[Override]
    public function generateToken(string $pendingId): string
    {
        $token = $this->retrieveToken($pendingId);

        if ($token === null) {
            $token = $this->randomStringToken();

            // Store the token in the session or other storage mechanism
            $this->session->setTempdata(self::SESSION_KEY, $token, $this->tokenTTLSeconds);
        }

        return $token;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function retrieveToken(string $pendingId): ?string
    {
        return $this->session->getTempdata(self::SESSION_KEY);
    }

    #[Override]
    public function deleteToken(string $pendingId): void
    {
        $this->session->removeTempdata(self::SESSION_KEY);
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
