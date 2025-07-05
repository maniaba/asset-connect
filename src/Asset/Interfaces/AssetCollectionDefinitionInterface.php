<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Asset\Interfaces;

interface AssetCollectionDefinitionInterface
{
    public function definition(AssetCollectionSetterInterface $definition): void;
}
