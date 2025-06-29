<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\AssetCollection;

use Maniaba\FileConnect\Asset\Asset;
use Maniaba\FileConnect\Asset\AssetVariant;
use Maniaba\FileConnect\Exceptions\FileVariantException;

final class AssetVariants
{
    public function __construct(
        private readonly string $storagePath,
        private readonly Asset $asset,
    ) {
    }

    /**
     * @throws FileVariantException
     */
    public function writeFile(string $name, string $data, string $mode = 'wb'): bool
    {
        helper('filesystem');
        $variant = $this->assetVariant($name);

        if (! write_file($variant->path, $data, $mode)) {
            throw new FileVariantException("Failed to write file to path: {$variant->path}");
        }

        // Update the size of the variant after writing
        $variant->size      = file_exists($variant->path) ? filesize($variant->path) : 0;
        $variant->processed = true;

        return true;
    }

    /**
     * Get the file path for a given variant name, then you can use this path to write the file.
     */
    public function assetVariant(string $name): AssetVariant
    {
        $fileNameWithoutExtension = pathinfo($this->asset->file_name, PATHINFO_FILENAME);
        $variantFileName          = $fileNameWithoutExtension . '-' . $name . '.' . $this->asset->extension;

        $variant = new AssetVariant([
            'name'      => $name,
            'path'      => $this->storagePath . $variantFileName,
            'size'      => 0, // Size will be updated after writing the file
            'processed' => false,
        ]);

        $this->asset->properties->fileVariant->addAssetVariant($variant);

        return $variant;
    }
}
