<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\AssetCollection;

use Maniaba\FileConnect\Enums\AssetExtension;
use Maniaba\FileConnect\Enums\AssetMimeType;
use Maniaba\FileConnect\Enums\AssetVisibility;
use Maniaba\FileConnect\Exceptions\InvalidArgumentException;
use Maniaba\FileConnect\Interfaces\Asset\AssetCollectionGetterInterface;
use Maniaba\FileConnect\Interfaces\Asset\AssetCollectionSetterInterface;
use Maniaba\FileConnect\Utils\PhpIni;

final class AssetCollection implements AssetCollectionSetterInterface, AssetCollectionGetterInterface
{
    /**
     * Array of allowed file extensions
     */
    private array $allowedExtensions = [];

    /**
     * Array of allowed MIME types
     */
    private array $allowedMimeTypes = [];

    /**
     * Maximum file size in bytes
     */
    private float|int $maxFileSize = 0;

    private AssetVisibility $visibility = AssetVisibility::PUBLIC;

    /**
     * Maximum number of files
     */
    private int $maximumNumberOfItemsInCollection = 0;

    public function __construct()
    {
        $this->setMaxFileSize(PhpIni::uploadMaxFilesizeBytes());
    }

    /**
     * {@inheritDoc}
     */
    public function allowedExtensions(AssetExtension|string ...$extensions): static
    {
        $this->allowedExtensions = array_unique(array_map(static function ($extension) {
            if ($extension instanceof AssetExtension) {
                return $extension->value; // Convert enum to string
            }

            if (! preg_match('/^[a-zA-Z0-9]+$/', $extension)) {
                throw new InvalidArgumentException('Invalid file extension: ' . $extension);
            }

            if ($extension === '') {
                throw new InvalidArgumentException('File extension cannot be empty.');
            }

            if ($extension[0] === '.') {
                throw new InvalidArgumentException('File extension should not start with a dot: ' . $extension);
            }

            return strtolower(trim($extension));
        }, $extensions));

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getAllowedExtensions(): array
    {
        return $this->allowedExtensions;
    }

    public function singleFileCollection(): static
    {
        $this->onlyKeepLatest(1);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function allowedMimeTypes(AssetMimeType|string ...$mimeTypes): static
    {
        $this->allowedMimeTypes = array_unique(array_map(static function ($mimeType) {
            if ($mimeType instanceof AssetMimeType) {
                return $mimeType->value; // Convert enum to string
            }

            if (! preg_match('/^[\w\-+]+\/[\w\-+.]+$/', $mimeType)) {
                throw new InvalidArgumentException('Invalid MIME type: ' . $mimeType);
            }
            if (trim($mimeType) === '') {
                throw new InvalidArgumentException('MIME type cannot be empty.');
            }

            return strtolower(trim($mimeType));
        }, $mimeTypes));

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getAllowedMimeTypes(): array
    {
        return $this->allowedMimeTypes;
    }

    /**
     * {@inheritDoc}
     */
    public function setMaxFileSize(float|int $maxFileSize): static
    {
        if ($maxFileSize < 0) {
            throw new InvalidArgumentException('Maximum file size must be a non-negative integer.');
        }

        $this->maxFileSize = $maxFileSize;

        return $this;
    }

    public function isSingleFileCollection(): bool
    {
        return $this->maximumNumberOfItemsInCollection === 1;
    }

    /**
     * {@inheritDoc}
     */
    public function getMaxFileSize(): int
    {
        return $this->maxFileSize;
    }

    /**
     * {@inheritDoc}
     */
    public function onlyKeepLatest(int $maximumNumberOfItemsInCollection): static
    {
        if ($maximumNumberOfItemsInCollection < 0) {
            throw new InvalidArgumentException('Maximum number of files must be a non-negative integer.');
        }

        $this->maximumNumberOfItemsInCollection = $maximumNumberOfItemsInCollection;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getMaximumNumberOfItemsInCollection(): int
    {
        return $this->maximumNumberOfItemsInCollection;
    }

    public function setVisibility(AssetVisibility $visibility): static
    {
        $this->visibility = $visibility;

        return $this;
    }

    public function getVisibility(): AssetVisibility
    {
        return $this->visibility;
    }
}
