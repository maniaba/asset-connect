<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\UrlGenerator\Interfaces;

use CodeIgniter\Router\RouteCollection;
use Maniaba\AssetConnect\Asset\Asset;
use Maniaba\AssetConnect\AssetVariants\AssetVariant;

interface UrlGeneratorInterface
{
    public static function routes(RouteCollection &$routes): void;

    /**
     * Params for the URL generation, route_to()
     */
    public static function params(Asset $asset, ?AssetVariant $variant, ?string $token = null): array;
}
