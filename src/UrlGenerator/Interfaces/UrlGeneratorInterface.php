<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\UrlGenerator\Interfaces;

use CodeIgniter\Router\RouteCollection;

interface UrlGeneratorInterface
{
    public static function routes(RouteCollection &$routes): void;

    /**
     * Params for the URL generation, route_to()
     */
    public static function params(int $assetId, ?string $variantName, string $filename, ?string $token = null): array;
}
