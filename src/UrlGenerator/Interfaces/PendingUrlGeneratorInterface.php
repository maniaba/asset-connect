<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\UrlGenerator\Interfaces;

use Maniaba\AssetConnect\Pending\PendingAsset;

interface PendingUrlGeneratorInterface
{
    /**
     * Params for pending URL generation, route_to()
     *
     * @return array<string, list<int|string|null>>
     */
    public static function pendingParams(PendingAsset $pendingAsset): array;
}
