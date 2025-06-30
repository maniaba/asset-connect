<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Asset\Interfaces;

interface AssetCollectionDefinitionInterface
{
    public function definition(AssetCollectionSetterInterface $definition): void;
}
