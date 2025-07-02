<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\PathGenerator;

use Maniaba\FileConnect\Asset\Interfaces\AssetCollectionGetterInterface;
use Maniaba\FileConnect\PathGenerator\Interfaces\PathGeneratorInterface;

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
    public function getFileRelativePath(PathGeneratorHelper $generatorHelper, AssetCollectionGetterInterface $collection): string
    {
        if (isset($this->fileRelativePath)) {
            return $this->fileRelativePath;
        }

        return $this->fileRelativePath = 'assets' . DIRECTORY_SEPARATOR . $generatorHelper->getDateTime() . DIRECTORY_SEPARATOR;
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
     * Get the path for conversions of the given media, relative to the root storage path.
     *
     * @param PathGeneratorHelper            $generatorHelper Helper for generating paths
     * @param AssetCollectionGetterInterface $collection      The asset collection
     *
     * @return string The path for variants
     */
    public function getPathForVariants(PathGeneratorHelper $generatorHelper, AssetCollectionGetterInterface $collection): string
    {
        $basePath = $this->path ?? $this->getPath($generatorHelper, $collection);

        return $basePath . 'variants' . DIRECTORY_SEPARATOR;
    }

    public function onCreatedDirectory(string $path): void
    {
        // Recrusively create empty index.html file to prevent directory listing
        log_message('debug', "Creating path '{$path}' for assets.");
    }
}
