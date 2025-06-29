<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Interfaces\Asset;

use Maniaba\FileConnect\Asset\Asset;
use Maniaba\FileConnect\AssetCollection\AssetVariants;
use Maniaba\FileConnect\Exceptions\FileVariantException;

interface FileVariantInterface
{
    /**
     * @param AssetVariants $variants File variants to be applied to the asset.
     * @param Asset         $asset    Asset to which the variants will be applied.
     *
     * @throws FileVariantException
     */
    public function variants(AssetVariants $variants, Asset $asset): void;

    // do variants need to be on a queue?
    public function fileVariantsOnQueue(): bool;
}
