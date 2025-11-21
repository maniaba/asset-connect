<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Pending\PendingSecurityToken;

use CodeIgniter\Session\Session;
use InvalidArgumentException;
use Maniaba\AssetConnect\Pending\Interfaces\PendingSecurityTokenInterface;
use Override;

readonly class SessionPendingSecurityToken implements PendingSecurityTokenInterface
{
    private const string SESSION_KEY_PREFIX = '__pending_security_token_';

    public function __construct(private int $tokenTTLSeconds = WEEK, private int $tokenLength = 16)
    {
        if ($this->tokenTTLSeconds <= 0) {
            throw new InvalidArgumentException('Token TTL must be a positive integer.');
        }

        if ($this->tokenLength <= 0 || $this->tokenLength > 64) {
            throw new InvalidArgumentException('Token length must be between 1 and 64 bytes.');
        }
    }

    #[Override]
    public function generateToken(string $pendingId): string
    {
        $token = bin2hex(random_bytes($this->tokenLength));

        // Store the token in the session or other storage mechanism
        /**
         * @var Session $session
         */
        $session = service('session');

        $session->setTempdata($this->sessionKey($pendingId), $token, $this->tokenTTLSeconds);

        return $token;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function retrieveToken(string $pendingId): ?string
    {
        /**
         * @var Session $session
         */
        $session = service('session');

        return $session->get($this->sessionKey($pendingId));
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function validateToken(string $pendingId, ?string $provided = null): bool
    {
        $provided = (string) $provided;
        $stored   = (string) $this->retrieveToken($pendingId);

        return hash_equals($stored, $provided);
    }

    private function sessionKey(string $pendingId): string
    {
        return self::SESSION_KEY_PREFIX . $pendingId;
    }

    #[Override]
    public function deleteToken(string $pendingId): void
    {
        /**
         * @var Session $session
         */
        $session = service('session');

        $session->remove($this->sessionKey($pendingId));
    }
}
