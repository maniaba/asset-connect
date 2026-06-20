<?php

declare(strict_types=1);

namespace Tests\Support\UrlGenerator;

use CodeIgniter\Router\RouteCollection;
use Maniaba\AssetConnect\Asset\Asset;
use Maniaba\AssetConnect\AssetVariants\AssetVariant;
use Maniaba\AssetConnect\UrlGenerator\Interfaces\UrlGeneratorInterface;
use Override;

final class RoutingTestUrlGenerator implements UrlGeneratorInterface
{
    public static bool $routesCalled = false;

    public static function reset(): void
    {
        self::$routesCalled = false;
    }

    #[Override]
    public static function routes(RouteCollection &$routes): void
    {
        unset($routes);

        self::$routesCalled = true;
    }

    #[Override]
    public static function params(Asset $asset, ?AssetVariant $variant, ?string $token = null): array
    {
        unset($variant, $token);

        return [
            'asset-connect.show'       => [$asset->id, $asset->file_name],
            'route-that-returns-false' => [$asset->id],
        ];
    }
}
