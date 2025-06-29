<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\AssetCollection;

use CodeIgniter\Entity\Entity;
use Config\Services;
use Maniaba\FileConnect\Asset\Asset;
use Maniaba\FileConnect\Asset\AssetVariant;
use Maniaba\FileConnect\Interfaces\Asset\AssetCollectionDefinitionInterface;
use Maniaba\FileConnect\Interfaces\Asset\AssetCollectionSetterInterface;
use Maniaba\FileConnect\Interfaces\Asset\AssetVariantsInterface;
use Maniaba\FileConnect\Interfaces\AssetCollection\CreateAssetVariantsInterface;

final class DefaultAssetCollection implements AssetCollectionDefinitionInterface, AssetVariantsInterface
{
    public function definition(AssetCollectionSetterInterface $definition): void
    {
    }

    public function checkAuthorization(array|Entity $entity, Asset $asset): bool
    {
        return true;
    }

    public function variants(CreateAssetVariantsInterface $variants, Asset $asset): void
    {
        $variants->onQueue = true; // Indicates that file variants should be processed on a queue.

        // Here you can add logic to process the asset and create variants.
        // For example, if you want to create a thumbnail or other variants,
        // you can use an image processing service.

        // Example:
        $variants->assetVariant('thuminal', static function (AssetVariant $variant, Asset $asset): void {
            $imageService = Services::image();
            $imageService->withFile($asset->path)
                ->fit(300, 300, 'center')
                ->text('Thumbnail')
                ->save($variant->path);
        });
    }
}
