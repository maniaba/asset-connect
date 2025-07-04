<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\AssetCollection;

use Maniaba\FileConnect\Asset\Interfaces\AssetCollectionDefinitionInterface;
use Maniaba\FileConnect\Asset\Interfaces\AssetCollectionSetterInterface;
use Override;

final class DefaultAssetCollection implements AssetCollectionDefinitionInterface
{
    #[Override]
    public function definition(AssetCollectionSetterInterface $definition): void
    {
    }
}
