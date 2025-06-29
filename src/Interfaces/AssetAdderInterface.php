<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Interfaces;

use CodeIgniter\Entity\Entity;
use CodeIgniter\Files\File;

interface AssetAdderInterface
{
    /**
     * Sets the entity to which the asset is added.
     */
    public function setSubject(Entity $entity): static;

    /**
     * Sets the file (path, object, etc.).
     *
     * @param File|string $file Should be a File object or a string path to the file.
     */
    public function setFile(File|string $file): static;

    /**
     * Sets the asset name (for display).
     */
    public function usingName(string $name): static;

    /**
     * Sets the file name.
     */
    public function usingFileName(string $fileName): static;

    /**
     * Sets the file size.
     */
    public function setFileSize(int $fileSize): static;

    /**
     * Sets additional file properties.
     */
    public function withCustomProperties(array $customProperties): static;

    /**
     * Saves to the asset collection.
     */
    public function toAssetCollection(string $collectionName = 'default', ?string $diskName = null): mixed;
}
