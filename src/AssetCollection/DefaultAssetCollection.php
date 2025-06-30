<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\AssetCollection;

use CodeIgniter\Entity\Entity;
use Maniaba\FileConnect\Asset\Asset;
use Maniaba\FileConnect\Interfaces\Asset\AssetCollectionDefinitionInterface;
use Maniaba\FileConnect\Interfaces\Asset\AssetCollectionSetterInterface;
use Maniaba\FileConnect\Interfaces\Asset\AssetVariantsInterface;
use Maniaba\FileConnect\Interfaces\AssetCollection\CreateAssetVariantsInterface;

final class DefaultAssetCollection implements AssetCollectionDefinitionInterface, AssetVariantsInterface
{
    public function definition(AssetCollectionSetterInterface $definition): void
    {
        $definition->onlyKeepLatest(2); // Keep only the latest 2 versions of the asset.
    }

    public function checkAuthorization(Entity $entity, Asset $asset): bool
    {
        return true;
    }

    public function variants(CreateAssetVariantsInterface $variants, Asset $asset): void
    {
        $variants->onQueue = true; // Indicates that file variants should be processed on a queue.
    }
}
