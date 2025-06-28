<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Interfaces\Asset;

use Maniaba\FileConnect\Asset\Asset;
use Maniaba\FileConnect\AssetCollection\FileVariants;
use Maniaba\FileConnect\Exceptions\FileVariantException;

interface FileVariantInterface
{
    /**
     * @param FileVariants $variants File variants to be applied to the asset.
     * @param Asset        $asset    Asset to which the variants will be applied.
     *
     * @throws FileVariantException
     */
    public function variants(FileVariants $variants, Asset $asset): void;
}
