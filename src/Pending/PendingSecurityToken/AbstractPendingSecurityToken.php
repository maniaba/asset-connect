<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Pending\PendingSecurityToken;

use InvalidArgumentException;
use Maniaba\AssetConnect\Pending\Interfaces\PendingSecurityTokenInterface;
use Maniaba\AssetConnect\Pending\PendingAsset;
use Override;
use Random\RandomException;

abstract class AbstractPendingSecurityToken implements PendingSecurityTokenInterface
{
    final public function __construct(protected readonly int $tokenTTLSeconds = WEEK, protected readonly int $tokenLength = 16)
    {
        if ($this->tokenTTLSeconds <= 0) {
            throw new InvalidArgumentException('Token TTL must be a positive integer.');
        }

        if ($this->tokenLength <= 0 || $this->tokenLength > 64) {
            throw new InvalidArgumentException('Token length must be between 1 and 64 bytes.');
        }

        $this->initialize();
    }

    /**
     * @throws RandomException
     */
    protected function randomStringToken(): string
    {
        return bin2hex(random_bytes($this->tokenLength));
    }

    #[Override]
    public function validateToken(PendingAsset $pendingAsset, ?string $tokenProvided = null): bool
    {
        $tokenProvided ??= $this->retrieveToken($pendingAsset->id);

        // If no token is provided and none is stored, validation fails
        if ($tokenProvided === null || $pendingAsset->security_token === null) {
            return false;
        }

        return hash_equals($pendingAsset->security_token, $tokenProvided);
    }

    abstract protected function initialize(): void;
}
