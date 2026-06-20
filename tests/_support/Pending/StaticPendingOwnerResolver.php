<?php

declare(strict_types=1);

namespace Tests\Support\Pending;

use Maniaba\AssetConnect\Pending\Interfaces\PendingOwnerResolverInterface;
use Override;

final class StaticPendingOwnerResolver implements PendingOwnerResolverInterface
{
    public static ?string $ownerId = null;

    public static function reset(): void
    {
        self::$ownerId = null;
    }

    #[Override]
    public function resolveOwnerId(): ?string
    {
        return self::$ownerId;
    }
}
