<?php

declare(strict_types=1);

namespace Tests\Support\AssetCollections;

use Maniaba\AssetConnect\Asset\Interfaces\AssetCollectionDefinitionInterface;
use Maniaba\AssetConnect\Asset\Interfaces\AssetCollectionSetterInterface;
use Override;
use Tests\Support\PathGenerators\FakeAssetPathGenerator;

final class MimeRestrictedTestAssetCollection implements AssetCollectionDefinitionInterface
{
    #[Override]
    public function definition(AssetCollectionSetterInterface $definition): void
    {
        $definition
            ->allowedExtensions('txt')
            ->allowedMimeTypes('image/png')
            ->setStorage('public')
            ->setPathGenerator(new FakeAssetPathGenerator('fake-assets/mime-restricted'));
    }
}
