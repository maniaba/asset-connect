<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Pending\PendingSecurityToken;

use CodeIgniter\HTTP\IncomingRequest;
use InvalidArgumentException;
use Maniaba\AssetConnect\Pending\Interfaces\PendingSecurityTokenInterface;
use Override;

class RequestPendingSecurityToken implements PendingSecurityTokenInterface
{
    /**
     * The field/header names that are checked when retrieving user token.
     */
    public function __construct(
        private readonly string $requestKey = 'pending_token',
        private readonly string $headerKey = 'X-Pending-Token',
        private readonly int $tokenLength = 16,
    ) {
        if ($this->tokenLength <= 0 || $this->tokenLength > 64) {
            throw new InvalidArgumentException('Token length must be between 1 and 64 bytes.');
        }
    }

    #[Override]
    public function generateToken(string $pendingId): string
    {
        return bin2hex(random_bytes($this->tokenLength));
    }

    #[Override]
    public function retrieveToken(string $pendingId): ?string
    {
        /**
         * @var IncomingRequest $request
         */
        $request = service('request');

        // Check headers first
        $headerToken = $request->getHeaderLine($this->headerKey);
        if ($headerToken !== '') {
            return $headerToken;
        }

        // Fallback to request variables (POST/GET)
        $requestToken = (string) ($request->getPost($this->requestKey) ?? $request->getGet($this->requestKey));
        if ($requestToken !== '') {
            return $requestToken;
        }

        return null;
    }

    #[Override]
    public function validateToken(string $pendingId, ?string $provided = null): bool
    {
        $provided = (string) $provided;
        $stored   = (string) $this->retrieveToken($pendingId);

        return hash_equals($stored, $provided);
    }

    #[Override]
    public function deleteToken(string $pendingId): void
    {
        // No persistent storage, so nothing to delete.
    }
}
