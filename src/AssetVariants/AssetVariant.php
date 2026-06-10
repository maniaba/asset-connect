<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\AssetVariants;

use CodeIgniter\Entity\Entity;
use Maniaba\AssetConnect\Exceptions\FileVariantException;
use Maniaba\AssetConnect\Storage\Interfaces\StorageDiskInterface;
use Maniaba\AssetConnect\Storage\StorageManager;
use Throwable;

/**
 * @property      string      $name
 * @property      string      $path
 * @property      bool        $processed
 * @property      int         $size
 * @property      string      $storage
 * @property-read string      $extension
 * @property-read string      $file_name
 * @property-read string|null $local_path
 * @property-read string      $mime_type
 * @property-read string      $relative_path
 * @property-read string      $relative_path_for_url
 */
final class AssetVariant extends Entity
{
    protected $attributes = [
        'name'      => '',
        'storage'   => '',
        'path'      => '',
        'size'      => 0,
        'processed' => false,
    ];
    protected $casts = [
        'size'      => 'int',
        'processed' => 'bool',
    ];

    /**
     * @throws FileVariantException
     */
    public function writeFile(string $data, string $mode = 'wb'): bool
    {
        unset($mode);

        try {
            $this->getStorageDisk()->write($this->path, $data);
        } catch (Throwable $exception) {
            $error = "Failed to write file to storage path: {$this->storage}:{$this->path}";

            throw new FileVariantException($error, $error, 0, $exception);
        }

        // Update the size of the variant after writing
        $this->size      = $this->getStorageDisk()->fileSize($this->path);
        $this->processed = true;

        return true;
    }

    protected function getRelativePath(): string
    {
        $relativePath = str_replace('\\', '/', $this->path);

        if ($relativePath === '') {
            throw new FileVariantException('Variant relative path is not set.');
        }

        // Ensure the relative path starts with a slash
        if ($relativePath[0] !== '/') {
            $relativePath = '/' . $relativePath;
        }

        return $relativePath;
    }

    protected function getRelativePathForUrl(): string
    {
        return str_replace('\\', '/', $this->getRelativePath());
    }

    protected function getFileName(): string
    {
        return basename($this->path);
    }

    protected function getExtension(): string
    {
        return pathinfo($this->path, PATHINFO_EXTENSION);
    }

    protected function getMimeType(): string
    {
        if (! $this->getStorageDisk()->fileExists($this->path)) {
            return '';
        }

        return $this->getStorageDisk()->mimeType($this->path);
    }

    protected function getLocalPath(): ?string
    {
        return $this->getStorageDisk()->localPath($this->path);
    }

    public function getStorageDisk(): StorageDiskInterface
    {
        return StorageManager::make()->disk($this->storage);
    }
}
