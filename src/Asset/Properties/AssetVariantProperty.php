<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Asset\Properties;

use Maniaba\FileConnect\AssetVariants\AssetVariant;
use Maniaba\FileConnect\Exceptions\InvalidArgumentException;

final class AssetVariantProperty extends BaseProperty
{
    public static function getName(): string
    {
        return 'asset_variants';
    }

    public function addAssetVariant(AssetVariant $variant): void
    {
        $this->set($variant->name, $variant);
    }

    public function updateAssetVariant(AssetVariant $variant): void
    {
        if ($this->hasAssetVariant($variant->name)) {
            $this->set($variant->name, $variant);
        } else {
            throw new InvalidArgumentException("Asset variant with name '{$variant->name}' does not exist.");
        }
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
     * @return array<string, AssetVariant>
     */
    public function &getVariants(): array
    {
        $variants = $this->properties ?? [];

        $variants         = array_map(static fn ($variant) => $variant instanceof AssetVariant ? $variant : new AssetVariant($variant), $variants);
        $this->properties = $variants;

        return $this->properties;
    }

    public function hasAssetVariant(string $name): bool
    {
        return $this->get($name) !== null;
    }

    public function removeAssetVariant(string $name): void
    {
        if ($this->hasAssetVariant($name)) {
            $this->remove($name);
        }
    }

    public function hasVariants(): bool
    {
        return $this->getVariants() !== [];
    }
}
