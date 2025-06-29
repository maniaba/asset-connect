<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Interfaces\AssetCollection;

use Closure;
use Maniaba\FileConnect\Asset\Asset;
use Maniaba\FileConnect\Asset\AssetVariant;

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
