<?php

declare(strict_types=1);

namespace Tests\Support\AssetCollections;

use Maniaba\AssetConnect\Asset\Interfaces\AssetCollectionDefinitionInterface;
use Maniaba\AssetConnect\Asset\Interfaces\AssetCollectionSetterInterface;
use Override;
use Tests\Support\PathGenerators\FakeAssetPathGenerator;

final class SingleFileTestAssetCollection implements AssetCollectionDefinitionInterface
{
    #[Override]
    public function definition(AssetCollectionSetterInterface $definition): void
    {
        $definition
            ->allowedExtensions('txt')
            ->setStorage('public')
            ->onlyKeepLatest(1)
            ->setPathGenerator(new FakeAssetPathGenerator('fake-assets/single-file'));
    }
}
