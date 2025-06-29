<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Asset\Properties;

use Maniaba\FileConnect\Asset\Asset;
use Maniaba\FileConnect\Asset\AssetVariant;

final class AssetVariantProperties extends BaseProperties
{
    public static function getName(): string
    {
        return 'asset_variants';
    }

    public function addAssetVariant(AssetVariant $variant): void
    {
        $this->set($variant->name, $variant);
    }

    public function getAssetVariant(string $name): ?AssetVariant
    {
        $variant = $this->get($name);

        if ($variant === null) {
            return null;
        }

        if ($variant instanceof AssetVariant) {
            return $variant;
        }

        return new AssetVariant($variant);
    }

    /**
     * Get all asset variants.
     *
     * @return list<AssetVariant>
     */
    public function getVariants(): array
    {
        $variants = $this->properties ?? [];

        return array_map(static fn ($variant) => $variant instanceof AssetVariant ? $variant : new AssetVariant($variant), $variants);
    }
}
