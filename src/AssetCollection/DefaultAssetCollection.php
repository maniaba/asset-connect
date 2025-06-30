<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\AssetCollection;

use Maniaba\FileConnect\Asset\Asset;
use Maniaba\FileConnect\Asset\Interfaces\AssetCollectionDefinitionInterface;
use Maniaba\FileConnect\Asset\Interfaces\AssetCollectionSetterInterface;
use Maniaba\FileConnect\AssetCollection\Interfaces\CreateAssetVariantsInterface;
use Maniaba\FileConnect\AssetVariants\Interfaces\AssetVariantsInterface;

final class DefaultAssetCollection implements AssetCollectionDefinitionInterface, AssetVariantsInterface
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
    }
}
