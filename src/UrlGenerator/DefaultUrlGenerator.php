<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\UrlGenerator;

use CodeIgniter\Router\RouteCollection;
use Maniaba\AssetConnect\Asset\Asset;
use Maniaba\AssetConnect\AssetVariants\AssetVariant;
use Maniaba\AssetConnect\Controllers\AssetConnectController;
use Maniaba\AssetConnect\UrlGenerator\Interfaces\UrlGeneratorInterface;
use Override;

class DefaultUrlGenerator implements UrlGeneratorInterface
{
    #[Override]
    public static function routes(RouteCollection &$routes): void
    {
        $routes->group('assets', static function (RouteCollection $routes) {
            // Bug in CodeIgniter routes, if we write only $1, we will get first segment as $1 and second segment as $2.
            $routes->get('(:num)/(:segment)', [AssetConnectController::class, 'show/$1/$3'], [
                'priority' => 100,
                'as'       => 'asset-connect.show',
            ]);

            $routes->get('(:num)/variant/(:segment)/(:segment)', [AssetConnectController::class, 'show/$1/$2'], [
                'priority' => 100,
                'as'       => 'asset-connect.show_variant',
            ]);

            $routes->get('temporary/(:segment)/(:segment)', [AssetConnectController::class, 'temporary/$1'], [
                'priority' => 100,
                'as'       => 'asset-connect.temporary',
            ]);

            $routes->get('temporary/(:segment)/variant/(:segment)/(:segment)', [AssetConnectController::class, 'temporary/$1'], [
                'priority' => 100,
                'as'       => 'asset-connect.temporary_variant',
            ]);
        });
    }

    #[Override]
    public static function params(Asset $asset, ?AssetVariant $variant, ?string $token = null): array
    {
        $variantName     = $variant->name ?? null;
        $variantFileName = $variant->file_name ?? null;

        return [
            'asset-connect.show'              => [$asset->id, $asset->file_name],
            'asset-connect.show_variant'      => [$asset->id, $variantName, $variantFileName],
            'asset-connect.temporary'         => [$token, $asset->file_name],
            'asset-connect.temporary_variant' => [$token, $variantName, $variantFileName],
        ];
    }
}
