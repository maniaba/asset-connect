<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\AssetCollection;

use CodeIgniter\Entity\Entity;
use Config\Services;
use Maniaba\FileConnect\Asset\Asset;
use Maniaba\FileConnect\Enums\AssetExtension;
use Maniaba\FileConnect\Enums\AssetMimeType;
use Maniaba\FileConnect\Interfaces\Asset\AssetCollectionDefinitionInterface;
use Maniaba\FileConnect\Interfaces\Asset\AssetCollectionSetterInterface;
use Maniaba\FileConnect\Interfaces\Asset\FileVariantInterface;

final class DefaultAssetCollection implements AssetCollectionDefinitionInterface, FileVariantInterface
{
    public function definition(AssetCollectionSetterInterface $definition): void
    {
        $definition->onlyKeepLatest(4);

        return;
        $definition->allowedExtensions('jpg', 'png', 'gif', AssetExtension::BMP)
            ->allowedMimeTypes(
                AssetMimeType::IMAGE_JPEG,
                AssetMimeType::IMAGE_PNG,
                AssetMimeType::IMAGE_GIF,
            )
            ->setMaxFileSize(5 * 1024 * 1024) // 5 MB
            ->onlyKeepLatest(50)
            ->singleFileCollection();
    }

    public function checkAuthorization(array|Entity $entity, Asset $asset): bool
    {
        return true;

        // Implement your authorization logic here.
        // For example, check if the user has permission to access this asset collection.
        return user_id() !== null; // Example: Check if user is logged in
    }

    public function variants(FileVariants $variants, Asset $asset): void
    {
        return;
        $variants->queue(true);

        $iamgeS = Services::image();

        $iamgeS->withFile($asset->getAbsolutePath());

        $iamgeS->fit(100, 100, 'center', 'top');
        $iamgeS->flip('horizontal');

        $iamgeS->save($asset->getAbsolutePath());

        $variants->variant('thumbnail', file_get_contents($iamgeS));

        $variants->variant('medium', $asset->getContent());

        $variants->variant('large', $asset->getContent());
    }
}
