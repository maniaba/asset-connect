<?php

declare(strict_types=1);

namespace Tests\Support;

use Maniaba\AssetConnect\Asset\Interfaces\AssetCollectionDefinitionInterface;
use Maniaba\AssetConnect\Asset\Interfaces\AssetCollectionSetterInterface;
use Override;

/**
 * Test Asset Collection for unit tests
 */
final class TestAssetCollection implements AssetCollectionDefinitionInterface
{
    #[Override]
    public function definition(AssetCollectionSetterInterface $definition): void
    {
    }
}
