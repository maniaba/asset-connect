<?php

declare(strict_types=1);

namespace Tests\Support\AssetCollections;

use Maniaba\AssetConnect\Asset\Asset;
use Maniaba\AssetConnect\Asset\Interfaces\AssetCollectionSetterInterface;
use Maniaba\AssetConnect\Asset\Interfaces\AuthorizableAssetCollectionDefinitionInterface;
use Override;

final class ProtectedTestAssetCollection implements AuthorizableAssetCollectionDefinitionInterface
{
    #[Override]
    public function definition(AssetCollectionSetterInterface $definition): void
    {
    }

    #[Override]
    public function checkAuthorization(Asset $asset): bool
    {
        unset($asset);

        return true;
    }
}
