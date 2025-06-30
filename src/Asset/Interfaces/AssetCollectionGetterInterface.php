<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Asset\Interfaces;

use Maniaba\FileConnect\Enums\AssetVisibility;

interface AssetCollectionGetterInterface
{
    /**
     * Returns the visibility setting for assets in the collection.
     *
     * @return AssetVisibility The visibility enum value for the assets
     */
    public function getVisibility(): AssetVisibility;

    /**
     * Determines if the collection is protected from unauthorized access.
     *
     * @return bool True if the collection is protected, false otherwise
     */
    public function isProtected(): bool;

    /**
     * Returns the maximum number of items allowed in the collection.
     *
     * @return int The maximum number of items that can be stored in this collection
     */
    public function getMaximumNumberOfItemsInCollection(): int;

    /**
     * Returns the maximum file size allowed for assets in the collection.
     *
     * @return int The maximum file size in bytes
     */
    public function getMaxFileSize(): int;

    /**
     * Checks if the collection can only contain a single file.
     *
     * @return bool True if the collection can only have one file, false if multiple files are allowed
     */
    public function isSingleFileCollection(): bool;

    /**
     * Returns the allowed MIME types for assets in the collection.
     *
     * @return array List of allowed MIME types
     */
    public function getAllowedMimeTypes(): array;

    /**
     * Returns the allowed file extensions for assets in the collection.
     *
     * @return array List of allowed file extensions
     */
    public function getAllowedExtensions(): array;
}
