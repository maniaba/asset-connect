<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\AssetCollection;

use Maniaba\FileConnect\Exceptions\FileVariantException;

final class FileVariants
{
    public function onQueue(?string $queue = null): FileVariants
    {
        return $this;
    }

    /**
     * @throws FileVariantException
     */
    public function writeFile(string $name, string $data, string $mode = 'wb'): bool
    {
        helper('filesystem');
        $path = $this->filePath($name);

        if (! write_file($path, $data, $mode)) {
            throw new FileVariantException("Failed to write file to path: {$path}");
        }

        return true;
    }

    /**
     * Get the file path for a given variant name, then you can use this path to write the file.
     */
    public function filePath(string $name): string
    {
    }
}
