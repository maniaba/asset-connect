<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\AssetCollection\Interfaces;

use Closure;
use Maniaba\AssetConnect\Asset\Asset;
use Maniaba\AssetConnect\AssetVariants\AssetVariant;

interface CreateAssetVariantsInterface
{
    /**
     * Creates a new asset variant with the given name and closure.
     *
     * @param string                            $name    The name of the variant.
     * @param Closure(AssetVariant, Asset):void $closure A closure that defines how to process the variant.
     */
    public function assetVariant(string $name, Closure $closure): ?AssetVariant;
}
