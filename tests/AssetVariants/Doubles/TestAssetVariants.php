<?php

declare(strict_types=1);

namespace Tests\AssetVariants\Doubles;

use Closure;
use Maniaba\FileConnect\Asset\Asset;
use Maniaba\FileConnect\AssetCollection\Interfaces\CreateAssetVariantsInterface;
use Maniaba\FileConnect\AssetVariants\AssetVariant;

/**
 * Test double for AssetVariants that uses TestPathGenerator instead of PathGenerator
 */
class TestAssetVariants implements CreateAssetVariantsInterface
{
    public bool $onQueue = false;

    public function __construct(
        private readonly TestPathGenerator $pathGenerator,
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
            'path'      => $this->pathGenerator->getPathForVariants() . $variantFileName,
            'size'      => 0, // Size will be updated after writing the file
            'processed' => false,
            'paths'     => [
                'storage_base_directory_path' => $this->pathGenerator->getStoreDirectoryForVariants(),
                'file_relative_path'          => $this->pathGenerator->getFileRelativePathForVariants(),
            ],
        ]);

        $this->asset->metadata->assetVariant->addAssetVariant($variant);

        return $variant;
    }
}
