<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Pending;

use Maniaba\AssetConnect\Pending\Interfaces\PendingSecurityTokenInterface;
use Maniaba\AssetConnect\Pending\PendingSecurityToken\SessionPendingSecurityToken;

class DefaultPendingStorage implements Interfaces\PendingStorageInterface
{
    protected PendingSecurityTokenInterface $tokenProvider;

    public function __construct(?PendingSecurityTokenInterface $tokenProvider = null)
    {
        $this->tokenProvider = $tokenProvider ?? new SessionPendingSecurityToken($this->getDefaultTTLSeconds());
    }

    public function generatePendingId(): string
    {
        return bin2hex(random_bytes(16));
    }

    public function pendingSecurityToken(): ?PendingSecurityTokenInterface
    {
        return $this->tokenProvider;
    }

    public function getBasePendingPath(): string
    {
        return WRITEPATH . DIRECTORY_SEPARATOR . 'assets_pending' . DIRECTORY_SEPARATOR;
    }

    public function getPendingFilePath(string $pendingId): string
    {
        return $this->getBasePendingPath() . $pendingId . DIRECTORY_SEPARATOR . 'file';
    }

    public function getPendingMetadataPath(string $pendingId): string
    {
        return $this->getBasePendingPath() . $pendingId . DIRECTORY_SEPARATOR . 'metadata.json';
    }

    public function getDefaultTTLSeconds(): int
    {
        return 86400; // 24 hours
    }
}
