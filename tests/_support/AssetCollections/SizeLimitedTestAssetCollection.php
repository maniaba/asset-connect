<?php

declare(strict_types=1);

namespace Tests\Support\AssetCollections;

use Maniaba\AssetConnect\Asset\Interfaces\AssetCollectionDefinitionInterface;
use Maniaba\AssetConnect\Asset\Interfaces\AssetCollectionSetterInterface;
use Override;
use Tests\Support\PathGenerators\FakeAssetPathGenerator;

final class SizeLimitedTestAssetCollection implements AssetCollectionDefinitionInterface
{
    #[Override]
    public function definition(AssetCollectionSetterInterface $definition): void
    {
        $definition
            ->allowedExtensions('txt')
            ->setStorage('public')
            ->setMaxFileSize(1)
            ->setPathGenerator(new FakeAssetPathGenerator('fake-assets/size-limited'));
    }
}
