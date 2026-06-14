<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\UrlGenerator;

use CodeIgniter\I18n\Time;
use CodeIgniter\Router\RouteCollection;
use Maniaba\AssetConnect\Asset\Asset;
use Maniaba\AssetConnect\AssetVariants\AssetVariant;
use Maniaba\AssetConnect\Enums\AssetVisibility;
use Maniaba\AssetConnect\Exceptions\InvalidArgumentException;
use Maniaba\AssetConnect\Storage\StorageManager;
use Maniaba\AssetConnect\UrlGenerator\Interfaces\UrlGeneratorInterface;

final readonly class UrlGenerator
{
    private function __construct(private Asset $asset)
    {
    }

    /**
     * Get the URL for the given asset, optionally specifying a variant.
     *
     * @param ?string $variantName The name of the variant to get the URL for, or empty for the original asset
     *
     * @return string The URL to the asset
     */
    public function getUrl(?string $variantName = null): string
    {
        $storage   = $this->asset->storage;
        $path      = $this->asset->path;
        $routeName = 'asset-connect.show';

        if ($variantName !== null && $variantName !== '' && $variantName !== '0') {
            $variant = $this->asset->metadata->assetVariant->getAssetVariant($variantName);

            if ($variant === null) {
                throw new InvalidArgumentException("Variant '{$variantName}' does not exist for asset '{$this->asset->id}'.");
            }

            $storage   = $variant->storage;
            $path      = $variant->path;
            $routeName = 'asset-connect.show_variant';
        }

        $disk = StorageManager::make()->disk($storage);

        if ($disk->visibility() === AssetVisibility::PROTECTED) {
            $url = self::routeTo($routeName, $this->asset, $variantName);

            return $url === '' ? '' : self::toAbsoluteUrl($url);
        }

        $publicUrl = $disk->publicUrl($path);

        return self::toAbsoluteUrl($publicUrl);
    }

    /**
     * Get a temporary URL for the given asset that expires after the specified time.
     *
     * @param Time    $expiration  The time when the URL should expire
     * @param ?string $variantName The name of the variant to get the URL for, or empty for the original asset
     *
     * @return string The temporary URL to the asset
     */
    public function getTemporaryUrl(Time $expiration, ?string $variantName = null): string
    {
        // Generate a temporary URL for the asset
        $token  = TempUrlToken::createToken($this->asset, $variantName, $expiration);
        $method = $variantName === null || $variantName === '' ? 'asset-connect.temporary' : 'asset-connect.temporary_variant';

        return self::toAbsoluteUrl(self::routeTo($method, $this->asset, $variantName, $token));
    }

    public static function routes(RouteCollection &$routes): void
    {
        /** @var \Maniaba\AssetConnect\Config\Asset $config */
        $config       = config('Asset');
        $urlGenerator = $config->defaultUrlGenerator;

        if ($urlGenerator === null) {
            return;
        }

        // check if the class implements UrlGeneratorInterface
        if (! is_subclass_of($urlGenerator, UrlGeneratorInterface::class)) {
            throw new InvalidArgumentException("The URL generator class '{$urlGenerator}' must implement the UrlGeneratorInterface.");
        }
        $urlGenerator::routes($routes);
    }

    public static function routeTo(string $routeName, Asset $asset, ?string $variantName, ?string $token = null): string
    {
        /** @var \Maniaba\AssetConnect\Config\Asset $config */
        $config       = config('Asset');
        $urlGenerator = $config->defaultUrlGenerator;

        if ($urlGenerator === null) {
            return '';
        }

        // check if the class implements UrlGeneratorInterface
        if (! is_subclass_of($urlGenerator, UrlGeneratorInterface::class)) {
            throw new InvalidArgumentException("The URL generator class '{$urlGenerator}' must implement the UrlGeneratorInterface.");
        }

        /** @var AssetVariant|null $variant */
        $variant = $asset->metadata->assetVariant->getAssetVariant((string) $variantName);

        $params = $urlGenerator::params($asset, $variant, $token);

        if (! isset($params[$routeName])) {
            throw new InvalidArgumentException("Route '{$routeName}' is not defined in the URL generator.");
        }

        $routeParams = $params[$routeName];

        $path = route_to($routeName, ...$routeParams);

        if ($path === false) {
            // Please define route with name asset-connect.show
            throw new InvalidArgumentException("Could not generate URL for asset '{$asset->id}' with variant '{$variantName}'. Please ensure the route '{$routeName}' is defined.");
        }

        return $path;
    }

    public static function create(Asset $asset): self
    {
        return new self($asset);
    }

    private static function toAbsoluteUrl(string $url): string
    {
        if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $url) === 1 || str_starts_with($url, '//')) {
            return $url;
        }

        return site_url(ltrim($url, '/'));
    }
}
