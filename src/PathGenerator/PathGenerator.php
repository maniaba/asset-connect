<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\PathGenerator;

use Maniaba\FileConnect\AssetCollection\AssetCollection;
use Maniaba\FileConnect\Exceptions\FileException;
use Maniaba\FileConnect\PathGenerator\Interfaces\PathGeneratorInterface;

final class PathGenerator
{
    private PathGeneratorHelper $helper;
    private PathGeneratorInterface $pathGenerator;

    public function __construct(
        private readonly AssetCollection $collection,
    ) {
        $this->helper        = new PathGeneratorHelper();
        $this->pathGenerator = $this->collection->getPathGenerator();
    }

    /**
     * Get the base storage directory where files will be stored.
     * This is the root directory for all files, which can be either protected or public.
     *
     * @return string The base storage directory path
     */
    public function getStoreDirectory(): string
    {
        $storeDirectory = $this->pathGenerator->getStoreDirectory($this->helper, $this->collection);

        // Ensure the directory exists
        $this->ensurePathExists($storeDirectory);

        return $storeDirectory;
    }

    /**
     * Get the relative path within the storage directory for a specific file.
     * This path is combined with the store directory to form the complete file path.
     *
     * @return string The relative path within the storage directory
     */
    public function getFileRelativePath(): string
    {
        return $this->pathGenerator->getFileRelativePath($this->helper, $this->collection);
    }

    /**
     * Get the complete path for the given media, combining store directory and relative path.
     * This is a convenience method that combines getStoreDirectory and getFileRelativePath.
     *
     * @return string The complete file path
     */
    public function getPath(): string
    {
        $path = $this->pathGenerator->getPath($this->helper, $this->collection);
        // Ensure the path ends with a DIRECTORY_SEPARATOR
        if (substr($path, -1) !== DIRECTORY_SEPARATOR) {
            $path .= DIRECTORY_SEPARATOR;
        }

        $this->ensurePathExists($path);

        return $path;
    }

    public function getPathForVariants(): string
    {
        $path = $this->pathGenerator->getPathForVariants($this->helper, $this->collection);
        // Ensure the path ends with a DIRECTORY_SEPARATOR
        if (substr($path, -1) !== DIRECTORY_SEPARATOR) {
            $path .= DIRECTORY_SEPARATOR;
        }

        $this->ensurePathExists($path);

        return $path;
    }

    /**
     * Ensure the path is valid and exists or create it if necessary.
     */
    private function ensurePathExists(string $path): void
    {
        if (! is_dir($path)) {
            if (! mkdir($path, 0755, true) && ! is_dir($path)) {
                $error = sprintf('Directory "%s" was not created', $path);

                throw new FileException($error, $error, 500);
            }
            // Ensure the directory is writable
            if (! is_writable($path)) {
                $error = sprintf('Directory "%s" is not writable', $path);

                throw new FileException($error, $error, 500);
            }

            // Ensure the directory is readable
            if (! is_readable($path)) {
                $error = sprintf('Directory "%s" is not readable', $path);

                throw new FileException($error, $error, 500);
            }

            $this->pathGenerator->onCreatedDirectory($path);
        }
    }
}
