<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Pending\PendingSecurityToken;

use InvalidArgumentException;
use Maniaba\AssetConnect\Config\Asset as AssetConfig;
use Maniaba\AssetConnect\Pending\Interfaces\PendingOwnerResolverInterface;
use Override;

final class OwnerPendingSecurityToken extends AbstractHmacPendingSecurityToken
{
    private PendingOwnerResolverInterface $ownerResolver;

    #[Override]
    public function generateToken(string $pendingId): string
    {
        $ownerId = $this->retrieveToken($pendingId);
        if ($ownerId === null || $ownerId === '') {
            throw new InvalidArgumentException('Pending owner resolver did not return an owner ID.');
        }

        return $this->createTokenDigest($pendingId, $ownerId);
    }

    #[Override]
    public function retrieveToken(string $pendingId): ?string
    {
        unset($pendingId);

        $ownerId = $this->ownerResolver->resolveOwnerId();
        if ($ownerId === null || $ownerId === '') {
            return null;
        }

        return $ownerId;
    }

    #[Override]
    public function deleteToken(string $pendingId): void
    {
        unset($pendingId);
    }

    #[Override]
    protected function initialize(): void
    {
        /** @var AssetConfig $config */
        $config = config('Asset');

        $resolver = $config->pendingOwnerResolver;
        if (is_string($resolver)) {
            if (! is_a($resolver, PendingOwnerResolverInterface::class, true)) {
                throw new InvalidArgumentException('Pending owner resolver must implement PendingOwnerResolverInterface.');
            }

            $resolver = new $resolver();
        }

        if (! $resolver instanceof PendingOwnerResolverInterface) {
            throw new InvalidArgumentException(
                'Pending owner resolver must be configured when using OwnerPendingSecurityToken.',
            );
        }

        $this->ownerResolver = $resolver;
    }
}
