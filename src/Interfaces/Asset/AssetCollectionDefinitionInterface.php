<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Interfaces\Asset;

interface AssetCollectionDefinitionInterface
{
    public function definition(AssetCollectionSetterInterface $definition): void;
}
