<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\UrlGenerator\Interfaces;

use CodeIgniter\I18n\Time;

interface UrlGeneratorInterface
{
    /**
     * Get the URL for the given asset, optionally specifying a variant.
     *
     * @param ?string $variantName The name of the variant to get the URL for, or empty for the original asset
     *
     * @return string The URL to the asset
     */
    public function getUrl(?string $variantName = null): string;

    /**
     * Get a temporary URL for the given asset that expires after the specified time.
     *
     * @param Time    $expiration  The time when the URL should expire
     * @param ?string $variantName The name of the variant to get the URL for, or empty for the original asset
     * @param array   $options     Additional options for the URL generation
     *
     * @return string The temporary URL to the asset
     */
    public function getTemporaryUrl(Time $expiration, ?string $variantName = null, array $options = []): string;
}
