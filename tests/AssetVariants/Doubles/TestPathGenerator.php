<?php

declare(strict_types=1);

namespace Tests\AssetVariants\Doubles;

use Maniaba\FileConnect\PathGenerator\PathGenerator;

/**
 * Test double for PathGenerator that allows us to test AssetVariants without needing a real PathGenerator
 */
class TestPathGenerator
{
    private string $pathForVariants = HOMEPATH . '/build/path/to/variants/';

    private string $storeDirectoryForVariants = HOMEPATH . '/build/path/to/';

    private string $fileRelativePathForVariants = 'variants/';

    /**
     * Set the path for variants
     */
    public function setPathForVariants(string $path): self
    {
        $this->pathForVariants = $path;

        return $this;
    }

    /**
     * Set the store directory for variants
     */
    public function setStoreDirectoryForVariants(string $directory): self
    {
        $this->storeDirectoryForVariants = $directory;

        return $this;
    }

    /**
     * Set the file relative path for variants
     */
    public function setFileRelativePathForVariants(string $path): self
    {
        $this->fileRelativePathForVariants = $path;

        return $this;
    }

    /**
     * Get the path for variants
     */
    public function getPathForVariants(): string
    {
        return $this->pathForVariants;
    }

    /**
     * Get the store directory for variants
     */
    public function getStoreDirectoryForVariants(): string
    {
        return $this->storeDirectoryForVariants;
    }

    /**
     * Get the file relative path for variants
     */
    public function getFileRelativePathForVariants(): string
    {
        return $this->fileRelativePathForVariants;
    }
}
