<?php

declare(strict_types=1);

namespace Tests\Support\AssetCollections;

use Maniaba\AssetConnect\Asset\Asset;
use Maniaba\AssetConnect\Asset\Interfaces\AssetCollectionDefinitionInterface;
use Maniaba\AssetConnect\Asset\Interfaces\AssetCollectionSetterInterface;
use Maniaba\AssetConnect\AssetVariants\AssetVariant;
use Maniaba\AssetConnect\AssetVariants\AssetVariants;
use Maniaba\AssetConnect\AssetVariants\AssetVariantsProcessor;
use Maniaba\AssetConnect\AssetVariants\Interfaces\AssetVariantsInterface;
use Maniaba\AssetConnect\AssetVariants\Interfaces\CreateAssetVariantsInterface;
use Override;
use Tests\Support\PathGenerators\FakeAssetPathGenerator;

final class QueuedVariantTestAssetCollection implements AssetCollectionDefinitionInterface, AssetVariantsInterface
{
    #[Override]
    public function definition(AssetCollectionSetterInterface $definition): void
    {
        $definition
            ->allowedExtensions('txt')
            ->setStorage('public')
            ->setPathGenerator(new FakeAssetPathGenerator('fake-assets/queued-variants'));
    }

    #[Override]
    public function variants(CreateAssetVariantsInterface $variants, Asset $asset): void
    {
        if ($variants instanceof AssetVariants || $variants instanceof AssetVariantsProcessor) {
            $variants->onQueue = true;
        }

        $variants->assetVariant(
            'queued',
            static function (AssetVariant $variant, Asset $asset): void {
                $variant->writeFile('queued for ' . $asset->file_name);
            },
        );
    }
}
