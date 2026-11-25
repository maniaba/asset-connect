<?php

declare(strict_types=1);

namespace Tests\Support\AssetCollections;

use Maniaba\AssetConnect\Asset\Asset;
use Maniaba\AssetConnect\Asset\Interfaces\AssetCollectionDefinitionInterface;
use Maniaba\AssetConnect\Asset\Interfaces\AssetCollectionSetterInterface;
use Maniaba\AssetConnect\AssetVariants\AssetVariant;
use Maniaba\AssetConnect\AssetVariants\Interfaces\AssetVariantsInterface;
use Maniaba\AssetConnect\AssetVariants\Interfaces\CreateAssetVariantsInterface;
use Override;

final class ImmediateVariantTestAssetCollection implements AssetCollectionDefinitionInterface, AssetVariantsInterface
{
    #[Override]
    public function definition(AssetCollectionSetterInterface $definition): void
    {
        $definition
            ->allowedExtensions('txt')
            ->setStorage('public');
    }

    #[Override]
    public function variants(CreateAssetVariantsInterface $variants, Asset $asset): void
    {
        $variants->assetVariant(
            'preview',
            static function (AssetVariant $variant, Asset $asset): void {
                $variant->writeFile('preview for ' . $asset->file_name);
            },
        );
    }
}
