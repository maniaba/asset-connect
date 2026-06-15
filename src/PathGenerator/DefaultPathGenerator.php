<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\PathGenerator;

use Maniaba\AssetConnect\Asset\Interfaces\AssetCollectionGetterInterface;
use Maniaba\AssetConnect\PathGenerator\Interfaces\PathGeneratorInterface;
use Override;

final class DefaultPathGenerator implements PathGeneratorInterface
{
    private const int UNIQUE_DIRECTORY_LENGTH = 20;

    private string $path;
    private string $fileRelativePath;

    /**
     * Get the relative path within the storage disk for a specific file.
     */
    #[Override]
    public function getFileRelativePath(PathGeneratorHelper $generatorHelper, AssetCollectionGetterInterface $collection): string
    {
        return $this->fileRelativePath ?? $this->fileRelativePath = $generatorHelper->getPathString(
            $generatorHelper->getDate(),
            $generatorHelper->getRandomId(self::UNIQUE_DIRECTORY_LENGTH),
        ) . '/';
    }

    /**
     * Get the relative directory for the original asset file.
     */
    #[Override]
    public function getPath(PathGeneratorHelper $generatorHelper, AssetCollectionGetterInterface $collection): string
    {
        return $this->path ?? $this->path = $this->getFileRelativePath($generatorHelper, $collection);
    }

    /**
     * Get the relative path within the storage disk for a specific variant file.
     */
    #[Override]
    public function getFileRelativePathForVariants(PathGeneratorHelper $generatorHelper, AssetCollectionGetterInterface $collection): string
    {
        return $this->getFileRelativePath($generatorHelper, $collection) . 'variants/';
    }

    /**
     * Get the relative directory for asset variants.
     */
    #[Override]
    public function getPathForVariants(PathGeneratorHelper $generatorHelper, AssetCollectionGetterInterface $collection): string
    {
        return $this->getFileRelativePathForVariants($generatorHelper, $collection);
    }
}
