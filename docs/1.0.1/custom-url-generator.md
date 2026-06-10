# Custom URL Generator

The URL Generator is a core component of CodeIgniter Asset Connect that handles the generation of URLs for accessing assets. While the library provides a default implementation (`DefaultUrlGenerator`), you can create your own custom URL generator to customize how asset URLs are generated.

## Why Create a Custom URL Generator?

There are several reasons why you might want to create a custom URL generator:

1. **Custom URL Structure**: You may want to use a different URL structure than the default one, such as including additional segments or using different naming conventions.
2. **Custom Security**: You might want to implement additional security measures in your URLs, such as signed URLs or custom token formats.
3. **SEO Optimization**: Custom URL structures can be more SEO-friendly, including relevant keywords or more readable paths.
4. **Legacy System Compatibility**: If you're integrating with a legacy system, you might need to generate URLs that match its expected format.

## Implementing a Custom URL Generator

To create a custom URL generator, you need to implement the `UrlGeneratorInterface`. This interface requires two methods:

1. `routes(RouteCollection &$routes): void` - Defines the routes needed for accessing assets.
2. `params(int $assetId, ?string $variantName, string $filename, ?string $token = null): array` - Generates the parameters for the routes based on asset information.

Here's a basic example of a custom URL generator:

```php
<?php

namespace App\UrlGenerators;

use CodeIgniter\Router\RouteCollection;
use Maniaba\AssetConnect\Controllers\AssetConnectController;
use Maniaba\AssetConnect\UrlGenerator\Interfaces\UrlGeneratorInterface;

class CustomUrlGenerator implements UrlGeneratorInterface
{
    public static function routes(RouteCollection &$routes): void
    {
        $routes->group('files', static function (RouteCollection $routes) {
            // Route for regular assets
            $routes->get('view/(:num)/(:segment)', [AssetConnectController::class, 'show/$1/$3'], [
                'priority' => 100,
                'as'       => 'custom-asset.show',
            ]);

            // Route for asset variants
            $routes->get('view/(:num)/variant/(:segment)/(:segment)', [AssetConnectController::class, 'show/$1/$2'], [
                'priority' => 100,
                'as'       => 'custom-asset.show_variant',
            ]);

            // Route for temporary assets
            $routes->get('temp/(:segment)/(:segment)', [AssetConnectController::class, 'temporary/$1'], [
                'priority' => 100,
                'as'       => 'custom-asset.temporary',
            ]);

            // Route for temporary asset variants
            $routes->get('temp/(:segment)/variant/(:segment)/(:segment)', [AssetConnectController::class, 'temporary/$1'], [
                'priority' => 100,
                'as'       => 'custom-asset.temporary_variant',
            ]);
        });
    }

    public static function params(int $assetId, ?string $variantName, string $filename, ?string $token = null): array
    {
        return [
            'custom-asset.show'              => [$assetId, $filename],
            'custom-asset.show_variant'      => [$assetId, $variantName, $filename],
            'custom-asset.temporary'         => [$token, $filename],
            'custom-asset.temporary_variant' => [$token, $variantName, $filename],
        ];
    }
}
```

This custom URL generator creates URLs with the following structure:

- Regular assets: `/files/view/{assetId}/{filename}`
- Asset variants: `/files/view/{assetId}/variant/{variantName}/{filename}`
- Temporary assets: `/files/temp/{token}/{filename}`
- Temporary asset variants: `/files/temp/{token}/variant/{variantName}/{filename}`

## More Advanced Examples

### SEO-Friendly URLs

If you want more SEO-friendly URLs, you might create a URL generator like this:

```php
<?php

namespace App\UrlGenerators;

use CodeIgniter\Router\RouteCollection;
use Maniaba\AssetConnect\Controllers\AssetConnectController;
use Maniaba\AssetConnect\UrlGenerator\Interfaces\UrlGeneratorInterface;

class SeoUrlGenerator implements UrlGeneratorInterface
{
    public static function routes(RouteCollection &$routes): void
    {
        $routes->group('media', static function (RouteCollection $routes) {
            // Route for regular assets with SEO-friendly structure
            $routes->get('(:num)/(:segment)', [AssetConnectController::class, 'show/$1/$3'], [
                'priority' => 100,
                'as'       => 'media.show',
            ]);

            // Route for asset variants with SEO-friendly structure
            $routes->get('(:num)/(:segment)/(:segment)', [AssetConnectController::class, 'show/$1/$2'], [
                'priority' => 100,
                'as'       => 'media.show_variant',
            ]);

            // Routes for temporary assets
            $routes->get('shared/(:segment)/(:segment)', [AssetConnectController::class, 'temporary/$1'], [
                'priority' => 100,
                'as'       => 'media.temporary',
            ]);

            $routes->get('shared/(:segment)/(:segment)/(:segment)', [AssetConnectController::class, 'temporary/$1'], [
                'priority' => 100,
                'as'       => 'media.temporary_variant',
            ]);
        });
    }

    public static function params(int $assetId, ?string $variantName, string $filename, ?string $token = null): array
    {
        return [
            'media.show' => [$assetId, $filename],
            'media.show_variant' => [$assetId, $variantName, $filename],
            'media.temporary' => [$token, $filename],
            'media.temporary_variant' => [$token, $variantName, $filename],
        ];
    }
}
```

This custom URL generator creates URLs with the following structure:

- Regular assets: `/media/{assetId}/{filename}`
- Asset variants: `/media/{assetId}/{variantName}/{filename}`
- Temporary assets: `/media/shared/{token}/{filename}`
- Temporary asset variants: `/media/shared/{token}/{variantName}/{filename}`

## Configuring Your Application to Use a Custom URL Generator

Once you've created your custom URL generator, you need to configure your application to use it. This is done in your `Config\Asset.php` file:

```php
<?php

namespace Config;

use App\UrlGenerators\CustomUrlGenerator;
use Maniaba\AssetConnect\Config\Asset as BaseAsset;

class Asset extends BaseAsset
{
    // Set your custom URL generator
    public string $urlGenerator = CustomUrlGenerator::class;

    // Other configuration options...
}
```

## How URL Generation Works

When you call methods like `getUrl()` or `getTemporaryUrl()` on an Asset object, the following process occurs:

1. The Asset object uses the UrlGeneratorTrait to call the appropriate method on the URL generator.
2. The URL generator generates the parameters for the route based on the asset information.
3. The parameters are used to generate the URL using CodeIgniter's `site_url()` function or directly returned if using a custom URL format.
4. The URL is returned to the caller.

## Best Practices

When creating a custom URL generator, consider the following best practices:

1. **Security**: Ensure that your URL structure doesn't expose sensitive information or allow unauthorized access to assets.
2. **Compatibility**: Make sure your custom URLs work with all asset types and variants.
3. **Performance**: Keep URL generation efficient, especially if you're generating URLs for many assets at once.
4. **Readability**: Create URLs that are human-readable and meaningful when possible.
5. **Consistency**: Maintain a consistent URL structure across your application.

## Conclusion

Creating a custom URL generator allows you to customize how asset URLs are generated in your application. By implementing the `UrlGeneratorInterface`, you can create URLs that match your specific requirements, whether for SEO or custom security measures.

Remember that changing the URL structure will affect all existing links to assets in your application, so it's best to implement a custom URL generator early in your development process or provide a migration path for existing links.
