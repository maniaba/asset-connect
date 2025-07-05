<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\PathGenerator\Interfaces;

use Maniaba\AssetConnect\Asset\Interfaces\AssetCollectionGetterInterface;
use Maniaba\AssetConnect\PathGenerator\PathGeneratorHelper;

interface PathGeneratorInterface
{
    /**
     * Get the base storage directory where files will be stored.
     * This is the root directory for all files, which can be either protected or public.
     *
     * @param PathGeneratorHelper            $generatorHelper Helper for generating paths
     * @param AssetCollectionGetterInterface $collection      The asset collection
     *
     * @return string The base storage directory path
     */
    public function getStoreDirectory(PathGeneratorHelper $generatorHelper, AssetCollectionGetterInterface $collection): string;

    /**
     * Get the relative path within the storage directory for a specific file.
     * This path is combined with the store directory to form the complete file path.
     *
     * @param PathGeneratorHelper            $generatorHelper Helper for generating paths
     * @param AssetCollectionGetterInterface $collection      The asset collection
     *
     * @return string The relative path within the storage directory
     */
    public function getFileRelativePath(PathGeneratorHelper $generatorHelper, AssetCollectionGetterInterface $collection): string;

    /**
     * Get the complete path for the given media, combining store directory and relative path.
     * This is a convenience method that combines getStoreDirectory and getFileRelativePath.
     *
     * @param PathGeneratorHelper            $generatorHelper Helper for generating paths
     * @param AssetCollectionGetterInterface $collection      The asset collection
     *
     * @return string The complete file path
     */
    public function getPath(PathGeneratorHelper $generatorHelper, AssetCollectionGetterInterface $collection): string;

    /**
     * Get the base storage directory where variant files will be stored.
     * This is the root directory for all variant files, which can be either protected or public.
     *
     * @param PathGeneratorHelper            $generatorHelper Helper for generating paths
     * @param AssetCollectionGetterInterface $collection      The asset collection
     *
     * @return string The base storage directory path for variants
     */
    public function getStoreDirectoryForVariants(PathGeneratorHelper $generatorHelper, AssetCollectionGetterInterface $collection): string;

    /**
     * Get the relative path within the storage directory for a specific variant file.
     * This path is combined with the store directory to form the complete variant file path.
     *
     * @param PathGeneratorHelper            $generatorHelper Helper for generating paths
     * @param AssetCollectionGetterInterface $collection      The asset collection
     *
     * @return string The relative path within the storage directory for variants
     */
    public function getFileRelativePathForVariants(PathGeneratorHelper $generatorHelper, AssetCollectionGetterInterface $collection): string;

    /**
     * Get the path for conversions of the given media, relative to the root storage path.
     * This is a convenience method that combines getStoreDirectoryForVariants and getFileRelativePathForVariants.
     *
     * @param PathGeneratorHelper            $generatorHelper Helper for generating paths
     * @param AssetCollectionGetterInterface $collection      The asset collection
     *
     * @return string The path for variants
     */
    public function getPathForVariants(PathGeneratorHelper $generatorHelper, AssetCollectionGetterInterface $collection): string;

    /**
     * @param string $path The path of the directory that was created.
     */
    public function onCreatedDirectory(string $path): void;
}
