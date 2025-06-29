<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\AssetCollection;

use CodeIgniter\Entity\Entity;
use Maniaba\FileConnect\Asset\Asset;
use Maniaba\FileConnect\Interfaces\Asset\AssetCollectionDefinitionInterface;
use Maniaba\FileConnect\Interfaces\Asset\AssetCollectionSetterInterface;
use Maniaba\FileConnect\Interfaces\Asset\FileVariantInterface;

final class DefaultAssetCollection implements AssetCollectionDefinitionInterface, FileVariantInterface
{
    public function definition(AssetCollectionSetterInterface $definition): void
    {
    }

    public function checkAuthorization(array|Entity $entity, Asset $asset): bool
    {
        return true;
    }

    public function variants(FileVariants $variants, Asset $asset): void
    {
    }
}
