<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\UrlGenerator;

use CodeIgniter\I18n\Time;
use Maniaba\FileConnect\Asset\Asset;
use Maniaba\FileConnect\Exceptions\InvalidArgumentException;
use Maniaba\FileConnect\UrlGenerator\Interfaces\UrlGeneratorInterface;

final class DefaultUrlGenerator implements UrlGeneratorInterface
{
    private Asset $asset;
    private bool $isProtectedCollection;

    public function __construct(Asset $asset)
    {
        $this->asset                 = $asset;
        $this->isProtectedCollection = $this->asset->metadata->basicInfo->isProtectedCollection();
    }

    public function getUrl(?string $variantName = null): string
    {
        $variantName  = $variantName ?: 'default';
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
        return route_to('asset-connect.show', (int) $this->asset->id, $variantName, $this->asset->file_name) ?? '';
    }

    public function getTemporaryUrl(Time $expiration, ?string $variantName = null, array $options = []): string
    {
        $variantName = $variantName ?: 'default';

        // Generate a temporary URL for the asset
        $token = TempUrlToken::createToken($this->asset, $variantName, $expiration);

        return route_to('asset-connect.temporary', $token, (int) $this->asset->id, $variantName, $this->asset->file_name) ?? '';
    }
}
