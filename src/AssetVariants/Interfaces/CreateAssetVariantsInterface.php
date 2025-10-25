<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\AssetVariants\Interfaces;

use Closure;
use Maniaba\AssetConnect\Asset\Asset;
use Maniaba\AssetConnect\AssetVariants\AssetVariant;
use Maniaba\AssetConnect\Enums\AssetExtension;

interface CreateAssetVariantsInterface
{
    /**
     * Creates a new asset variant with the given name and closure.
     *
     * @param string                            $name      The name of the variant.
     * @param Closure(AssetVariant, Asset):void $closure   A closure that defines how to process the variant.
     * @param AssetExtension|string|null        $extension Optional custom file extension. If null, uses original file's extension.
     */
    public function assetVariant(string $name, Closure $closure, AssetExtension|string|null $extension = null): ?AssetVariant;
}
