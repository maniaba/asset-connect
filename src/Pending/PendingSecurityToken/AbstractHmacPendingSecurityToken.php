<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Pending\PendingSecurityToken;

use InvalidArgumentException;
use Maniaba\AssetConnect\Config\Asset as AssetConfig;
use Maniaba\AssetConnect\Pending\PendingAsset;
use Override;

abstract class AbstractHmacPendingSecurityToken extends AbstractPendingSecurityToken
{
    private const string HMAC_ALGORITHM = 'sha256';

    private ?string $securityKey = null;

    #[Override]
    final public function validateToken(PendingAsset $pendingAsset, ?string $tokenProvided = null): bool
    {
        unset($tokenProvided);

        $ownerProof = $this->retrieveToken($pendingAsset->id);
        if ($ownerProof === null || $ownerProof === '' || $pendingAsset->security_token === null) {
            return false;
        }

        return hash_equals(
            $pendingAsset->security_token,
            $this->createTokenDigest($pendingAsset->id, $ownerProof),
        );
    }

    final protected function createTokenDigest(string $pendingId, string $ownerProof): string
    {
        return hash_hmac(
            self::HMAC_ALGORITHM,
            "asset-connect.pending-token.v1\n" . $pendingId . "\n" . $ownerProof,
            $this->getSecurityKey(),
        );
    }

    private function getSecurityKey(): string
    {
        if ($this->securityKey !== null) {
            return $this->securityKey;
        }

        /** @var AssetConfig $assetConfig */
        $assetConfig = config('Asset');

        $securityKey = $assetConfig->pendingSecurityKey;
        if ($securityKey === null || $securityKey === '') {
            $encryptionConfig = config('Encryption');
            $encryptionKey    = $encryptionConfig->key ?? null;
            $securityKey      = is_string($encryptionKey) ? $encryptionKey : null;
        }

        if ($securityKey === null || $securityKey === '') {
            throw new InvalidArgumentException(
                'Pending security key must be configured via Config\Asset::$pendingSecurityKey or Config\Encryption::$key.',
            );
        }

        return $this->securityKey = $securityKey;
    }
}
