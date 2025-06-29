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

    public function variants(AssetVariants $variants, Asset $asset): void
    {
        // Here you can add logic to process the asset and create variants.
        // For example, if you want to create a thumbnail or other variants,
        // you can use an image processing service.

        // Example:
        // $imageService = Services::image();
        // $thumbnail = $imageService->withFile($asset->path)
        //     ->fit(300, 300, 'center')
        //     ->getFile();
        //
        // $variants->writeFile('thumbnail', $thumbnail->getRealPath());
    }

    public function fileVariantsOnQueue(): bool
    {
        return true; // Indicates that file variants should be processed on a queue.
    }
}
