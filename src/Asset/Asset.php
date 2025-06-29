<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Asset;

use CodeIgniter\Entity\Entity;
use CodeIgniter\Files\File;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\I18n\Time;
use InvalidArgumentException;
use Maniaba\FileConnect\AssetCollection\AssetCollectionDefinitionFactory;
use Maniaba\FileConnect\Interfaces\Asset\AssetCollectionDefinitionInterface;

/**
 * @property      string            $collection   name of the collection to which the asset belongs (md5 hash of the class name)
 * @property      Time              $created_at   timestamp when the asset was created
 * @property      Time|null         $deleted_at   timestamp when the asset was deleted, null if not deleted
 * @property      int               $entity_id    identifier for the entity to which the asset belongs
 * @property      string            $entity_type  type of the entity to which the asset belongs(md5 hash of the class name)
 * @property      File|UploadedFile $file         file object associated with the asset, null if not set
 * @property      string            $file_name    name of the file associated with the asset
 * @property-read string            $extension    file extension of the asset
 * @property      int               $id           identifier for the asset
 * @property      string            $mime_type    MIME type of the file
 * @property      string            $name         name of the asset
 * @property      int               $order        order of the asset in the collection
 * @property      string            $path         path to the file on the server
 * @property      string            $path_dirname directory path of the file on the server
 * @property      Properties        $properties
 * @property      int               $size         size of the file in bytes
 * @property      Time              $updated_at   timestamp when the asset was last updated
 */
final class Asset extends Entity
{
    protected $casts = [
        'id'          => 'int',
        'entity_type' => 'string',
        'entity_id'   => 'int',
        'order'       => 'int',
        'collection'  => 'string',
        'size'        => 'int',
    ];

    public function setEntityType(Entity|string $entityType): static
    {
        if ($entityType instanceof Entity) {
            $entityType = $entityType::class;
        } elseif (! class_exists($entityType) || ($entityType !== Entity::class && ! is_subclass_of($entityType, Entity::class))) {
            throw new InvalidArgumentException('Entity type must be a valid Entity class or instance.');
        }

        $this->attributes['entity_type'] = md5($entityType);

        return $this;
    }

    /**
     * Set the file associated with the asset.
     *
     * @param File|string|UploadedFile $file The file to associate with the asset.
     *
     * @throws InvalidArgumentException If the file is not a valid File or UploadedFile instance, or $collection is not valid string
     */
    public function setCollection(AssetCollectionDefinitionInterface|string $collection): static
    {
        if ($collection instanceof AssetCollectionDefinitionInterface) {
            $collection = $collection::class;
        } else {
            AssetCollectionDefinitionFactory::validateStringClass($collection);
        }

        $this->attributes['collection'] = md5($collection);

        return $this;
    }

    protected function setProperties(?string $properties): static
    {
        $this->attributes['properties'] = $properties;

        return $this;
    }

    protected function getProperties(): Properties
    {
        $value = $this->attributes['properties'] ?? null;

        if ($value === null) {
            return $this->attributes['properties'] = new Properties();
        }

        if ($value instanceof Properties) {
            return $value;
        }

        return new Properties(json_decode((string) $value, true));
    }

    protected function getExtension(): string
    {
        return $this->file->getExtension();
    }

    protected function getPathDirname(): string
    {
        if ($this->path === null || $this->path === '') {
            throw new \Maniaba\FileConnect\Exceptions\InvalidArgumentException('Path directory not set.');
        }

        return dirname($this->path) . DIRECTORY_SEPARATOR;
    }

    /**
     * Check if the asset is an image
     *
     * @return bool True if the asset is an image, false otherwise
     */
    public function isImage(): bool
    {
        return \Maniaba\FileConnect\Enums\AssetMimeType::isImage($this->mime_type);
    }

    /**
     * Check if the asset is a document
     *
     * @return bool True if the asset is a document, false otherwise
     */
    public function isDocument(): bool
    {
        return \Maniaba\FileConnect\Enums\AssetMimeType::isDocument($this->mime_type);
    }

    /**
     * Check if the asset is a video
     *
     * @return bool True if the asset is a video, false otherwise
     */
    public function isVideo(): bool
    {
        return \Maniaba\FileConnect\Enums\AssetMimeType::isVideo($this->mime_type);
    }

    /**
     * Check if the asset is an audio
     *
     * @return bool True if the asset is an audio, false otherwise
     */
    public function isAudio(): bool
    {
        return \Maniaba\FileConnect\Enums\AssetMimeType::isAudio($this->mime_type);
    }

    /**
     * Check if the asset is an archive
     *
     * @return bool True if the asset is an archive, false otherwise
     */
    public function isArchive(): bool
    {
        return \Maniaba\FileConnect\Enums\AssetMimeType::isArchive($this->mime_type);
    }

    /**
     * Check if the asset is a text file
     *
     * @return bool True if the asset is a text file, false otherwise
     */
    public function isText(): bool
    {
        return \Maniaba\FileConnect\Enums\AssetMimeType::isText($this->mime_type);
    }

    /**
     * Check if the asset is a web file
     *
     * @return bool True if the asset is a web file, false otherwise
     */
    public function isWeb(): bool
    {
        return \Maniaba\FileConnect\Enums\AssetMimeType::isWeb($this->mime_type);
    }

    /**
     * Check if the asset is a programming file
     *
     * @return bool True if the asset is a programming file, false otherwise
     */
    public function isProgramming(): bool
    {
        return \Maniaba\FileConnect\Enums\AssetMimeType::isProgramming($this->mime_type);
    }

    /**
     * Check if the asset is a font
     *
     * @return bool True if the asset is a font, false otherwise
     */
    public function isFont(): bool
    {
        return \Maniaba\FileConnect\Enums\AssetMimeType::isFont($this->mime_type);
    }

    /**
     * Check if the asset is a design file
     *
     * @return bool True if the asset is a design file, false otherwise
     */
    public function isDesign(): bool
    {
        return \Maniaba\FileConnect\Enums\AssetMimeType::isDesign($this->mime_type);
    }

    /**
     * Check if the asset is a database file
     *
     * @return bool True if the asset is a database file, false otherwise
     */
    public function isDatabase(): bool
    {
        return \Maniaba\FileConnect\Enums\AssetMimeType::isDatabase($this->mime_type);
    }

    /**
     * Check if the asset is an ebook
     *
     * @return bool True if the asset is an ebook, false otherwise
     */
    public function isEbook(): bool
    {
        return \Maniaba\FileConnect\Enums\AssetMimeType::isEbook($this->mime_type);
    }

    /**
     * Check if the asset is a CAD file
     *
     * @return bool True if the asset is a CAD file, false otherwise
     */
    public function isCad(): bool
    {
        return \Maniaba\FileConnect\Enums\AssetMimeType::isCad($this->mime_type);
    }

    /**
     * Check if the asset is a scientific file
     *
     * @return bool True if the asset is a scientific file, false otherwise
     */
    public function isScientific(): bool
    {
        return \Maniaba\FileConnect\Enums\AssetMimeType::isScientific($this->mime_type);
    }

    /**
     * Check if the asset is a configuration file
     *
     * @return bool True if the asset is a configuration file, false otherwise
     */
    public function isConfiguration(): bool
    {
        return \Maniaba\FileConnect\Enums\AssetMimeType::isConfiguration($this->mime_type);
    }

    /**
     * Check if the asset is an executable
     *
     * @return bool True if the asset is an executable, false otherwise
     */
    public function isExecutable(): bool
    {
        return \Maniaba\FileConnect\Enums\AssetMimeType::isExecutable($this->mime_type);
    }

    /**
     * Check if the asset is a vector graphic
     *
     * @return bool True if the asset is a vector graphic, false otherwise
     */
    public function isVectorGraphic(): bool
    {
        return \Maniaba\FileConnect\Enums\AssetMimeType::isVectorGraphic($this->mime_type);
    }

    /**
     * Check if the asset is a raster graphic
     *
     * @return bool True if the asset is a raster graphic, false otherwise
     */
    public function isRasterGraphic(): bool
    {
        return \Maniaba\FileConnect\Enums\AssetMimeType::isRasterGraphic($this->mime_type);
    }

    /**
     * Check if the asset is a spreadsheet
     *
     * @return bool True if the asset is a spreadsheet, false otherwise
     */
    public function isSpreadsheet(): bool
    {
        return \Maniaba\FileConnect\Enums\AssetMimeType::isSpreadsheet($this->mime_type);
    }

    /**
     * Check if the asset is a presentation
     *
     * @return bool True if the asset is a presentation, false otherwise
     */
    public function isPresentation(): bool
    {
        return \Maniaba\FileConnect\Enums\AssetMimeType::isPresentation($this->mime_type);
    }
}
