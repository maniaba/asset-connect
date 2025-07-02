<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\AssetCollection;

use Maniaba\FileConnect\Asset\Asset;
use Maniaba\FileConnect\Asset\Interfaces\AssetCollectionDefinitionInterface;
use Maniaba\FileConnect\Asset\Interfaces\AssetCollectionSetterInterface;
use Maniaba\FileConnect\Asset\Interfaces\AuthorizableAssetCollectionDefinitionInterface;
use Maniaba\FileConnect\AssetCollection\Interfaces\CreateAssetVariantsInterface;
use Maniaba\FileConnect\AssetVariants\Interfaces\AssetVariantsInterface;

final class DefaultAssetCollection implements AssetCollectionDefinitionInterface, AssetVariantsInterface, AuthorizableAssetCollectionDefinitionInterface
{
    public function definition(AssetCollectionSetterInterface $definition): void
    {
        $definition->onlyKeepLatest(2); // Keep only the latest 2 versions of the asset.
    }

    public function checkAuthorization(Asset $asset): bool
    {
        return true;
    }

    public function variants(CreateAssetVariantsInterface $variants, Asset $asset): void
    {
        $variants->onQueue = true; // Indicates that file variants should be processed on a queue.

        $variants->assetVariant('thumbnail', static function ($variant) use ($asset) {
            // Here you can define how to create the thumbnail variant.
            // For example, using an image processing library to resize the asset.
            $variant->writeFile(file_get_contents($asset->path));
        });

        $variants->assetVariant('medium', static function ($variant) use ($asset) {
            // Here you can define how to create the medium variant.
            // For example, resizing the asset to a medium size.
            $variant->writeFile(file_get_contents($asset->path));
        });

        $variants->assetVariant('large', static function ($variant) use ($asset) {
            // Here you can define how to create the large variant.
            // For example, resizing the asset to a large size.
            $variant->writeFile(file_get_contents($asset->path));
        });
    }
}
