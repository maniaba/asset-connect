<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\UrlGenerator\Traits;

use CodeIgniter\I18n\Time;
use Maniaba\FileConnect\UrlGenerator\UrlGenerator;

trait UrlGeneratorTrait
{
    /**
     * Get the URL for this asset, optionally specifying a variant
     *
     * @param string|null $variantName The name of the variant to get the URL for, or empty for the original asset
     *
     * @return string The URL to the asset
     */
    public function getUrl(?string $variantName = null): string
    {
        return site_url($this->getUrlRelative($variantName));
    }

    /**
     * Get the relative URL for this asset, which is the path without the base URL
     *
     * @return string The relative URL to the asset
     */
    public function getUrlRelative(?string $variantName = null): string
    {
        return UrlGenerator::create($this)->getUrl($variantName);
    }

    /**
     * Get a temporary URL for this asset that expires after the specified time
     *
     * @param Time        $expiration  The time when the URL should expire
     * @param string|null $variantName The name of the variant to get the URL for, or empty for the original asset
     *
     * @return string The temporary URL to the asset
     */
    public function getTemporaryUrl(Time $expiration, ?string $variantName = null): string
    {
        return site_url($this->getTemporaryUrlRelative($expiration, $variantName));
    }

    /**
     * Get a temporary relative URL for this asset that expires after the specified time
     *
     * @param Time    $expiration  The time when the URL should expire
     * @param ?string $variantName The name of the variant to get the URL for, or empty for the original asset
     *
     * @return string The temporary related URL to the asset
     */
    public function getTemporaryUrlRelative(Time $expiration, ?string $variantName = null): string
    {
        return UrlGenerator::create($this)->getTemporaryUrl($expiration, $variantName);
    }
}
