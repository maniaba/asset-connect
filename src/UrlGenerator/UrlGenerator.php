<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\UrlGenerator;

use CodeIgniter\I18n\Time;
use CodeIgniter\Router\RouteCollection;
use Maniaba\AssetConnect\Asset\Asset;
use Maniaba\AssetConnect\AssetVariants\AssetVariant;
use Maniaba\AssetConnect\Exceptions\InvalidArgumentException;
use Maniaba\AssetConnect\UrlGenerator\Interfaces\UrlGeneratorInterface;

final readonly class UrlGenerator
{
    private bool $isProtectedCollection;

    private function __construct(private Asset $asset)
    {
        $this->isProtectedCollection = $this->asset->metadata->basicInfo->isProtectedCollection();
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
        $relativePath = $this->asset->relative_path_for_url;

        // If the asset is not part of a protected collection, return the URL directly
        if (! $this->isProtectedCollection) {
            if ($variantName !== null && $variantName !== '' && $variantName !== '0') {
                $variant = $this->asset->metadata->assetVariant->getAssetVariant($variantName);

                if ($variant === null) {
                    throw new InvalidArgumentException("Variant '{$variantName}' does not exist for asset '{$this->asset->id}'.");
                }

                $relativePath = $variant->relative_path_for_url;
            }

            return site_url($relativePath);
        }

        // If the asset is part of a protected collection, return the URL with go to controller route
        $method = $variantName === null || $variantName === '' ? 'asset-connect.show' : 'asset-connect.show_variant';

        return self::routeTo($method, $this->asset, $variantName);
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

        return self::routeTo($method, $this->asset, $variantName, $token);
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
}
