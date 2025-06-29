<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Interfaces\Asset;

use Maniaba\FileConnect\Asset\Asset;
use Maniaba\FileConnect\Exceptions\FileVariantException;
use Maniaba\FileConnect\Interfaces\AssetCollection\CreateAssetVariantsInterface;

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
