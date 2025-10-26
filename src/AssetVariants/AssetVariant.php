<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\AssetVariants;

use CodeIgniter\Entity\Entity;
use CodeIgniter\Files\File;
use Maniaba\AssetConnect\Exceptions\FileVariantException;
use stdClass;

/**
 * @property      string                                                                          $name
 * @property      string                                                                          $path
 * @property      array{storage_base_directory_path: string, file_relative_path: string}|stdClass $paths
 * @property      bool                                                                            $processed
 * @property      int                                                                             $size
 * @property-read string                                                                          $extension
 * @property-read string                                                                          $file_name
 * @property-read string                                                                          $mime_type
 * @property-read string                                                                          $relative_path
 * @property-read string                                                                          $relative_path_for_url
 */
final class AssetVariant extends Entity
{
    protected $attributes = [
        'name'      => '',
        'path'      => '',
        'size'      => 0,
        'processed' => false,
    ];
    protected $casts = [
        'size'      => 'int',
        'processed' => 'bool',
        'paths'     => 'json',
    ];

    /**
     * @throws FileVariantException
     */
    public function writeFile(string $data, string $mode = 'wb'): bool
    {
        helper('filesystem');

        if (! write_file($this->path, $data, $mode)) {
            $error = "Failed to write file to path: {$this->path}";

            throw new FileVariantException($error, $error);
        }

        // Update the size of the variant after writing
        $this->size      = file_exists($this->path) ? filesize($this->path) : 0;
        $this->processed = true;

        return true;
    }

    protected function getRelativePath(): string
    {
        // / replace storage_base_directory_path from path
        $storageBasePath = $this->paths->storage_base_directory_path ?? null;
        if ($storageBasePath === null) {
            throw new FileVariantException('Storage base directory path is not set.');
        }

        $relativePath = str_replace($storageBasePath, '', $this->path);

        // Ensure the relative path starts with a slash
        if ($relativePath[0] !== '/') {
            $relativePath = '/' . $relativePath;
        }

        return $relativePath;
    }

    protected function getRelativePathForUrl(): string
    {
        $relativePath = $this->getRelativePath();

        // Replace backslashes with forward slashes for URL compatibility
        return str_replace('\\', '/', $relativePath);
    }

    protected function getFileName(): string
    {
        // extract filename from path
        return basename($this->path);
    }

    protected function getExtension(): string
    {
        return pathinfo($this->path, PATHINFO_EXTENSION);
    }

    // get file mime type
    protected function getMimeType(): string
    {
        if (! file_exists($this->path)) {
            return 'application/octet-stream';
        }

        $file = new File($this->path);

        return $file->getMimeType();
    }
}
