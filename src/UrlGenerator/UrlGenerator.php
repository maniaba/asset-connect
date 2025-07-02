<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\UrlGenerator;

use CodeIgniter\I18n\Time;
use Maniaba\FileConnect\Asset\Asset;
use Maniaba\FileConnect\Exceptions\InvalidArgumentException;
use Maniaba\FileConnect\UrlGenerator\Interfaces\UrlGeneratorInterface;

final class UrlGenerator implements UrlGeneratorInterface
{
    private Asset $asset;
    private bool $isProtectedCollection;

    public function __construct(Asset $asset)
    {
        $this->asset                 = $asset;
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
            if ($variantName) {
                $variant = $this->asset->metadata->fileVariant->getAssetVariant($variantName);

                if ($variant === null) {
                    throw new InvalidArgumentException("Variant '{$variantName}' does not exist for asset '{$this->asset->id}'.");
                }

                $relativePath = $variant->relative_path_for_url;
            }

            return site_url($relativePath);
        }

        // If the asset is part of a protected collection, return the URL with go to controller route
        $path = $variantName === null || $variantName === '' ?
            route_to('asset-connect.show', (int) $this->asset->id, $this->asset->file_name) :
            route_to('asset-connect.show_variant', (int) $this->asset->id, $variantName, $this->asset->file_name);

        if ($path === false) {
            // Please define route with name asset-connect.show
            throw new InvalidArgumentException("Could not generate URL for asset '{$this->asset->id}' with variant '{$variantName}'. Please ensure the route 'asset-connect.show' is defined.");
        }

        return site_url($path);
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
        $token = TempUrlToken::createToken($this->asset, $variantName, $expiration);

        return route_to('asset-connect.temporary', $token, (int) $this->asset->id, $variantName, $this->asset->file_name) ?? '';
    }
}
