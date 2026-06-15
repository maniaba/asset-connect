<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\PathGenerator\Interfaces;

use Maniaba\AssetConnect\Asset\Interfaces\AssetCollectionGetterInterface;
use Maniaba\AssetConnect\PathGenerator\PathGeneratorHelper;

interface PathGeneratorInterface
{
    /**
     * Get the relative directory where the original asset file will be stored.
     */
    public function getFileRelativePath(PathGeneratorHelper $generatorHelper, AssetCollectionGetterInterface $collection): string;

    /**
     * Get the relative directory for the original asset file.
     */
    public function getPath(PathGeneratorHelper $generatorHelper, AssetCollectionGetterInterface $collection): string;

    /**
     * Get the relative directory where variant files will be stored.
     */
    public function getFileRelativePathForVariants(PathGeneratorHelper $generatorHelper, AssetCollectionGetterInterface $collection): string;

    /**
     * Get the relative directory for asset variants.
     */
    public function getPathForVariants(PathGeneratorHelper $generatorHelper, AssetCollectionGetterInterface $collection): string;
}
