<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\AssetVariants\Interfaces;

use Maniaba\FileConnect\Asset\Asset;
use Maniaba\FileConnect\AssetCollection\Interfaces\CreateAssetVariantsInterface;
use Maniaba\FileConnect\Exceptions\FileVariantException;

interface AssetVariantsInterface
{
    /**
     * @param CreateAssetVariantsInterface $variants File variants to be applied to the asset.
     * @param Asset                        $asset    Asset to which the variants will be applied.
     *
     * @throws FileVariantException
     */
    public function variants(CreateAssetVariantsInterface $variants, Asset $asset): void;
}
