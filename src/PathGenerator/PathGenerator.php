<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\PathGenerator;

use Maniaba\AssetConnect\AssetCollection\AssetCollection;
use Maniaba\AssetConnect\PathGenerator\Interfaces\PathGeneratorInterface;

final readonly class PathGenerator
{
    private PathGeneratorHelper $helper;
    private PathGeneratorInterface $pathGenerator;

    public function __construct(
        private AssetCollection $collection,
    ) {
        $this->helper        = new PathGeneratorHelper();
        $this->pathGenerator = $this->collection->getPathGenerator();
    }

    /**
     * Get the relative path within the storage disk for a specific file.
     */
    public function getFileRelativePath(): string
    {
        return $this->normalizeDirectoryPath(
            $this->pathGenerator->getFileRelativePath($this->helper, $this->collection),
        );
    }

    /**
     * Get the relative directory for the original asset file.
     */
    public function getPath(): string
    {
        return $this->normalizeDirectoryPath(
            $this->pathGenerator->getPath($this->helper, $this->collection),
        );
    }

    /**
     * Get the relative path within the storage disk for a specific variant file.
     */
    public function getFileRelativePathForVariants(): string
    {
        return $this->normalizeDirectoryPath(
            $this->pathGenerator->getFileRelativePathForVariants($this->helper, $this->collection),
        );
    }

    /**
     * Get the relative directory for asset variants.
     */
    public function getPathForVariants(): string
    {
        return $this->normalizeDirectoryPath(
            $this->pathGenerator->getPathForVariants($this->helper, $this->collection),
        );
    }

    private function normalizeDirectoryPath(string $path): string
    {
        $path = trim(str_replace('\\', '/', $path), '/');

        if ($path === '') {
            return '';
        }

        return $path . '/';
    }
}
