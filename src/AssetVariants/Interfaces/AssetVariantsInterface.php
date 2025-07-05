<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\AssetVariants\Interfaces;

use Maniaba\AssetConnect\Asset\Asset;
use Maniaba\AssetConnect\AssetVariants\Interfaces\CreateAssetVariantsInterface;
use Maniaba\AssetConnect\Exceptions\FileVariantException;

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
