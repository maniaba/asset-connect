<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\UrlGenerator\Traits;

use CodeIgniter\I18n\Time;
use Maniaba\AssetConnect\UrlGenerator\UrlGenerator;

trait UrlGeneratorTrait
{
    /**
     * Get the URL for this asset, optionally specifying a variant
     *
     * @param string|null $variantName The name of the variant to get the URL for, or empty for the original asset
     *
     * @return string The URL to the asset
     *
     * @example
     * // Get URL for original asset
     * $url = $asset->getUrl();
     * // Output: "https://example.com/assets/images/file.jpg"
     *
     * // Get URL for a thumbnail variant
     * $url = $asset->getUrl('thumbnail');
     * // Output: "https://example.com/assets/images/variants/thumbnail/file.jpg"
     */
    public function getUrl(?string $variantName = null, bool $forceDownload = false): string
    {
        return site_url($this->getUrlRelative($variantName, $forceDownload));
    }

    /**
     * Get the relative URL for this asset, which is the path without the base URL
     *
     * @param string|null $variantName The name of the variant to get the URL for, or empty for the original asset
     * @param bool $forceDownload Whether to force the browser to download the file instead of displaying it
     *
     * @return string The relative URL to the asset
     *
     * @example
     * // Get relative URL for original asset
     * $url = $asset->getUrlRelative();
     * // Output: "/assets/images/file.jpg"
     *
     * // Get relative URL for a thumbnail variant
     * $url = $asset->getUrlRelative('thumbnail');
     * // Output: "/assets/images/variants/thumbnail/file.jpg"
     *
     * // Get relative URL with force download
     * $url = $asset->getUrlRelative(null, true);
     * // Output: "/assets/images/file.jpg?download=force"
     */
    public function getUrlRelative(?string $variantName = null, bool $forceDownload = false): string
    {
        return UrlGenerator::create($this)->getUrl($variantName) . ($forceDownload ? '?download=force' : '');
    }

    /**
     * Get a temporary URL for this asset that expires after the specified time
     *
     * @param Time        $expiration  The time when the URL should expire
     * @param string|null $variantName The name of the variant to get the URL for, or empty for the original asset
     * @param bool        $forceDownload Whether to force the browser to download the file instead of displaying it
     *
     * @return string The temporary URL to the asset
     *
     * @example
     * // Import the Time class
     * use CodeIgniter\I18n\Time;
     *
     * // Get temporary URL for original asset
     * $expiration = Time::now()->addHours(1);
     * $url = $asset->getTemporaryUrl($expiration);
     * // Output: "https://example.com/assets/abc123def456789/images/file.jpg"
     *
     * // Get temporary URL for a thumbnail variant
     * $url = $asset->getTemporaryUrl($expiration, 'thumbnail');
     * // Output: "https://example.com/assets/abc123def456789/images/variants/thumbnail/file.jpg"
     *
     * // Get temporary URL with force download
     * $url = $asset->getTemporaryUrl($expiration, null, true);
     * // Output: "https://example.com/assets/abc123def456789/images/file.jpg?download=force"
     */
    public function getTemporaryUrl(Time $expiration, ?string $variantName = null, bool $forceDownload = false): string
    {
        return site_url($this->getTemporaryUrlRelative($expiration, $variantName, $forceDownload));
    }

    /**
     * Get a temporary relative URL for this asset that expires after the specified time
     *
     * @param Time    $expiration  The time when the URL should expire
     * @param ?string $variantName The name of the variant to get the URL for, or empty for the original asset
     * @param bool    $forceDownload Whether to force the browser to download the file instead of displaying it
     *
     * @return string The temporary relative URL to the asset
     *
     * @example
     * // Import the Time class
     * use CodeIgniter\I18n\Time;
     *
     * // Get temporary relative URL for original asset
     * $expiration = Time::now()->addHours(1);
     * $url = $asset->getTemporaryUrlRelative($expiration);
     * // Output: "/assets/abc123def456789/images/file.jpg"
     *
     * // Get temporary relative URL for a thumbnail variant
     * $url = $asset->getTemporaryUrlRelative($expiration, 'thumbnail');
     * // Output: "/assets/abc123def456789/images/variants/thumbnail/file.jpg"
     *
     * // Get temporary relative URL with force download
     * $url = $asset->getTemporaryUrlRelative($expiration, null, true);
     * // Output: "/assets/abc123def456789/images/file.jpg?download=force"
     */
    public function getTemporaryUrlRelative(Time $expiration, ?string $variantName = null, bool $forceDownload = false): string
    {
        return UrlGenerator::create($this)->getTemporaryUrl($expiration, $variantName) . ($forceDownload ? '?download=force' : '');
    }
}
