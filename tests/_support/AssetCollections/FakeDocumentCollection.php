<?php

declare(strict_types=1);

namespace Tests\Support\AssetCollections;

use Maniaba\AssetConnect\Asset\Interfaces\AssetCollectionDefinitionInterface;
use Maniaba\AssetConnect\Asset\Interfaces\AssetCollectionSetterInterface;
use Override;
use Tests\Support\PathGenerators\FakeAssetPathGenerator;

final class FakeDocumentCollection implements AssetCollectionDefinitionInterface
{
    #[Override]
    public function definition(AssetCollectionSetterInterface $definition): void
    {
        $definition
            ->allowedExtensions('txt')
            ->setStorage('public')
            ->setPathGenerator(new FakeAssetPathGenerator('fake-assets/documents'));
    }
}
