<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\PathGenerator;

use Maniaba\AssetConnect\Asset\Interfaces\AssetCollectionGetterInterface;
use Maniaba\AssetConnect\PathGenerator\Interfaces\PathGeneratorInterface;
use Override;

final class DefaultPathGenerator implements PathGeneratorInterface
{
    private string $path;
    private string $storeDirectory;
    private string $fileRelativePath;

    /**
     * Get the base storage directory where files will be stored.
     * This is the root directory for all files, which can be either protected or public.
     *
     * @param PathGeneratorHelper            $generatorHelper Helper for generating paths
     * @param AssetCollectionGetterInterface $collection      The asset collection
     *
     * @return string The base storage directory path
     */
    #[Override]
    public function getStoreDirectory(PathGeneratorHelper $generatorHelper, AssetCollectionGetterInterface $collection): string
    {
        if (isset($this->storeDirectory)) {
            return $this->storeDirectory;
        }

        $basePath = $collection->isProtected() ? WRITEPATH : realpath(ROOTPATH . 'public') . DIRECTORY_SEPARATOR;

        return $this->storeDirectory = $basePath;
    }

    /**
     * Get the relative path within the storage directory for a specific file.
     * This path is combined with the store directory to form the complete file path.
     *
     * @param PathGeneratorHelper            $generatorHelper Helper for generating paths
     * @param AssetCollectionGetterInterface $collection      The asset collection
     *
     * @return string The relative path within the storage directory
     */
    #[Override]
    public function getFileRelativePath(PathGeneratorHelper $generatorHelper, AssetCollectionGetterInterface $collection): string
    {
        return $this->fileRelativePath ?? $this->fileRelativePath = 'assets' . DIRECTORY_SEPARATOR . $generatorHelper->getDateTime() . DIRECTORY_SEPARATOR;
    }

    /**
     * Get the complete path for the given media, combining store directory and relative path.
     * This is a convenience method that combines getStoreDirectory and getFileRelativePath.
     *
     * @param PathGeneratorHelper            $generatorHelper Helper for generating paths
     * @param AssetCollectionGetterInterface $collection      The asset collection
     *
     * @return string The complete file path
     */
    #[Override]
    public function getPath(PathGeneratorHelper $generatorHelper, AssetCollectionGetterInterface $collection): string
    {
        if (isset($this->path)) {
            return $this->path;
        }

        $storeDirectory   = $this->getStoreDirectory($generatorHelper, $collection);
        $fileRelativePath = $this->getFileRelativePath($generatorHelper, $collection);

        return $this->path = $storeDirectory . $fileRelativePath;
    }

    /**
     * Get the base storage directory where variant files will be stored.
     * This is the root directory for all variant files, which can be either protected or public.
     *
     * @param PathGeneratorHelper            $generatorHelper Helper for generating paths
     * @param AssetCollectionGetterInterface $collection      The asset collection
     *
     * @return string The base storage directory path for variants
     */
    #[Override]
    public function getStoreDirectoryForVariants(PathGeneratorHelper $generatorHelper, AssetCollectionGetterInterface $collection): string
    {
        // Variants use the same base storage directory as the original files
        return $this->getStoreDirectory($generatorHelper, $collection);
    }

    /**
     * Get the relative path within the storage directory for a specific variant file.
     * This path is combined with the store directory to form the complete variant file path.
     *
     * @param PathGeneratorHelper            $generatorHelper Helper for generating paths
     * @param AssetCollectionGetterInterface $collection      The asset collection
     *
     * @return string The relative path within the storage directory for variants
     */
    #[Override]
    public function getFileRelativePathForVariants(PathGeneratorHelper $generatorHelper, AssetCollectionGetterInterface $collection): string
    {
        // Get the file relative path for the original file
        $fileRelativePath = $this->getFileRelativePath($generatorHelper, $collection);

        // Append 'variants' directory to the relative path
        return $fileRelativePath . 'variants' . DIRECTORY_SEPARATOR;
    }

    /**
     * Get the path for conversions of the given media, relative to the root storage path.
     * This is a convenience method that combines getStoreDirectoryForVariants and getFileRelativePathForVariants.
     *
     * @param PathGeneratorHelper            $generatorHelper Helper for generating paths
     * @param AssetCollectionGetterInterface $collection      The asset collection
     *
     * @return string The path for variants
     */
    #[Override]
    public function getPathForVariants(PathGeneratorHelper $generatorHelper, AssetCollectionGetterInterface $collection): string
    {
        // Always use the store directory and file relative path for variants
        $storeDirectory   = $this->getStoreDirectoryForVariants($generatorHelper, $collection);
        $fileRelativePath = $this->getFileRelativePathForVariants($generatorHelper, $collection);

        return $storeDirectory . $fileRelativePath;
    }

    #[Override]
    public function onCreatedDirectory(string $path): void
    {
        // Recrusively create empty index.html file to prevent directory listing
        log_message('debug', "Creating path '{$path}' for assets.");
    }
}
