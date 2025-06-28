<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Interfaces\Asset;

interface AssetCollectionInterface
{
    public function definition(AssetCollectionSetterInterface $definition): void;
}
