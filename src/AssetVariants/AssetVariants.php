<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\AssetVariants;

use Closure;
use Maniaba\FileConnect\Asset\Asset;
use Maniaba\FileConnect\AssetCollection\Interfaces\CreateAssetVariantsInterface;

final class AssetVariants implements CreateAssetVariantsInterface
{
    public bool $onQueue = false;

    public function __construct(
        private readonly string $storagePath,
        private readonly Asset $asset,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function assetVariant(string $name, Closure $closure): AssetVariant
    {
        $fileNameWithoutExtension = pathinfo($this->asset->file_name, PATHINFO_FILENAME);
        $fileExtension            = pathinfo($this->asset->file_name, PATHINFO_EXTENSION);

        $variantFileName = $fileNameWithoutExtension . '-' . $name . '.' . $fileExtension;

        $variant = new AssetVariant([
            'name'      => $name,
            'path'      => $this->storagePath . $variantFileName,
            'size'      => 0, // Size will be updated after writing the file
            'processed' => false,
        ]);

        $this->asset->metadata->fileVariant->addAssetVariant($variant);

        return $variant;
    }
}
