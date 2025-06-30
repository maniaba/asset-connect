<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Asset;

use CodeIgniter\Entity\Entity;
use CodeIgniter\Files\File;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\I18n\Time;
use InvalidArgumentException;
use Maniaba\FileConnect\AssetCollection\AssetCollectionDefinitionFactory;
use Maniaba\FileConnect\Enums\AssetMimeType;
use Maniaba\FileConnect\Interfaces\Asset\AssetCollectionDefinitionInterface;
use Maniaba\FileConnect\UrlGenerator\DefaultUrlGenerator;
use Maniaba\FileConnect\UrlGenerator\UrlGeneratorInterface;

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

        $this->properties->basicInfo->entityTypeClass($entityType);

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

        $this->properties->basicInfo->collectionClass($collection);

        $this->attributes['collection'] = md5($collection);

        return $this;
    }

    private Properties $propertiesInstance;

    protected function setProperties(Properties|string|null $properties): static
    {
        if (is_string($properties)) {
            $properties = new Properties(json_decode($properties, true));
        } elseif ($properties === null) {
            $properties = new Properties();
        }

        $this->propertiesInstance = $properties;

        return $this;
    }

    protected function getProperties(): Properties
    {
        if (! isset($this->propertiesInstance)) {
            $value = $this->attributes['properties'] ?? null;

            if (is_string($value)) {
                $value = json_decode($value, true);
                $this->propertiesInstance = new Properties($value);
            } elseif (is_array($value)) {
                $this->propertiesInstance = new Properties($value);
            } else if ($value instanceof Properties) {
                $this->propertiesInstance = $value;
            } else {
                $this->propertiesInstance = new Properties();
            }

        }

        return $this->propertiesInstance;
    }

    public function toRawArray(bool $onlyChanged = false, bool $recursive = false): array
    {
        $rawArray = parent::toRawArray($onlyChanged, $recursive);
        $rawArray['properties'] = json_encode($this->getProperties());

        return $rawArray;
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
        return AssetMimeType::isImage($this->mime_type);
    }

    /**
     * Check if the asset is a document
     *
     * @return bool True if the asset is a document, false otherwise
     */
    public function isDocument(): bool
    {
        return AssetMimeType::isDocument($this->mime_type);
    }

    /**
     * Check if the asset is a video
     *
     * @return bool True if the asset is a video, false otherwise
     */
    public function isVideo(): bool
    {
        return AssetMimeType::isVideo($this->mime_type);
    }

    /**
     * Check if the asset is an audio
     *
     * @return bool True if the asset is an audio, false otherwise
     */
    public function isAudio(): bool
    {
        return AssetMimeType::isAudio($this->mime_type);
    }

    /**
     * Check if the asset is an archive
     *
     * @return bool True if the asset is an archive, false otherwise
     */
    public function isArchive(): bool
    {
        return AssetMimeType::isArchive($this->mime_type);
    }

    /**
     * Check if the asset is a text file
     *
     * @return bool True if the asset is a text file, false otherwise
     */
    public function isText(): bool
    {
        return AssetMimeType::isText($this->mime_type);
    }

    /**
     * Check if the asset is a web file
     *
     * @return bool True if the asset is a web file, false otherwise
     */
    public function isWeb(): bool
    {
        return AssetMimeType::isWeb($this->mime_type);
    }

    /**
     * Check if the asset is a programming file
     *
     * @return bool True if the asset is a programming file, false otherwise
     */
    public function isProgramming(): bool
    {
        return AssetMimeType::isProgramming($this->mime_type);
    }

    /**
     * Check if the asset is a font
     *
     * @return bool True if the asset is a font, false otherwise
     */
    public function isFont(): bool
    {
        return AssetMimeType::isFont($this->mime_type);
    }

    /**
     * Check if the asset is a design file
     *
     * @return bool True if the asset is a design file, false otherwise
     */
    public function isDesign(): bool
    {
        return AssetMimeType::isDesign($this->mime_type);
    }

    /**
     * Check if the asset is a database file
     *
     * @return bool True if the asset is a database file, false otherwise
     */
    public function isDatabase(): bool
    {
        return AssetMimeType::isDatabase($this->mime_type);
    }

    /**
     * Check if the asset is an ebook
     *
     * @return bool True if the asset is an ebook, false otherwise
     */
    public function isEbook(): bool
    {
        return AssetMimeType::isEbook($this->mime_type);
    }

    /**
     * Check if the asset is a CAD file
     *
     * @return bool True if the asset is a CAD file, false otherwise
     */
    public function isCad(): bool
    {
        return AssetMimeType::isCad($this->mime_type);
    }

    /**
     * Check if the asset is a scientific file
     *
     * @return bool True if the asset is a scientific file, false otherwise
     */
    public function isScientific(): bool
    {
        return AssetMimeType::isScientific($this->mime_type);
    }

    /**
     * Check if the asset is a configuration file
     *
     * @return bool True if the asset is a configuration file, false otherwise
     */
    public function isConfiguration(): bool
    {
        return AssetMimeType::isConfiguration($this->mime_type);
    }

    /**
     * Check if the asset is an executable
     *
     * @return bool True if the asset is an executable, false otherwise
     */
    public function isExecutable(): bool
    {
        return AssetMimeType::isExecutable($this->mime_type);
    }

    /**
     * Check if the asset is a vector graphic
     *
     * @return bool True if the asset is a vector graphic, false otherwise
     */
    public function isVectorGraphic(): bool
    {
        return AssetMimeType::isVectorGraphic($this->mime_type);
    }

    /**
     * Check if the asset is a raster graphic
     *
     * @return bool True if the asset is a raster graphic, false otherwise
     */
    public function isRasterGraphic(): bool
    {
        return AssetMimeType::isRasterGraphic($this->mime_type);
    }

    /**
     * Check if the asset is a spreadsheet
     *
     * @return bool True if the asset is a spreadsheet, false otherwise
     */
    public function isSpreadsheet(): bool
    {
        return AssetMimeType::isSpreadsheet($this->mime_type);
    }

    /**
     * Check if the asset is a presentation
     *
     * @return bool True if the asset is a presentation, false otherwise
     */
    public function isPresentation(): bool
    {
        return AssetMimeType::isPresentation($this->mime_type);
    }

    /**
     * Get a URL generator for this asset
     *
     * @param UrlGeneratorInterface|null $urlGenerator Custom URL generator to use, or null to use the default
     * @param Entity|null                $entity       The entity to check authorization for, or null to skip authorization
     *
     * @return UrlGeneratorInterface The URL generator for this asset
     */
    public function getUrlGenerator(?UrlGeneratorInterface $urlGenerator = null, ?Entity $entity = null): UrlGeneratorInterface
    {
        if ($urlGenerator !== null) {
            return $urlGenerator;
        }

        return new DefaultUrlGenerator($this, $entity);
    }

    /**
     * Get the URL for this asset, optionally specifying a variant
     *
     * @param string                     $variantName  The name of the variant to get the URL for, or empty for the original asset
     * @param UrlGeneratorInterface|null $urlGenerator Custom URL generator to use, or null to use the default
     * @param Entity|null                $entity       The entity to check authorization for, or null to skip authorization
     *
     * @return string The URL to the asset
     */
    public function getUrl(string $variantName = '', ?UrlGeneratorInterface $urlGenerator = null, ?Entity $entity = null): string
    {
        return $this->getUrlGenerator($urlGenerator, $entity)->getUrl($variantName);
    }

    /**
     * Get a temporary URL for this asset that expires after the specified time
     *
     * @param Time                       $expiration   The time when the URL should expire
     * @param string                     $variantName  The name of the variant to get the URL for, or empty for the original asset
     * @param array                      $options      Additional options for the URL generation
     * @param UrlGeneratorInterface|null $urlGenerator Custom URL generator to use, or null to use the default
     * @param Entity|null                $entity       The entity to check authorization for, or null to skip authorization
     *
     * @return string The temporary URL to the asset
     */
    public function getTemporaryUrl(Time $expiration, string $variantName = '', array $options = [], ?UrlGeneratorInterface $urlGenerator = null, ?Entity $entity = null): string
    {
        return $this->getUrlGenerator($urlGenerator, $entity)->getTemporaryUrl($expiration, $variantName, $options);
    }

    /**
     * Get the class name of the asset collection definition for this asset
     *
     * @return string|null The class name of the asset collection definition, or null if not set
     * @throws InvalidArgumentException If the collection class does not exist or does not implement AssetCollectionDefinitionInterface
     */
    public function getAssetCollectionDefinitionClass(): ?string
    {
        $collectionClass = $this->getProperties()->basicInfo->collectionClassName();

        if ($collectionClass === null) {
            return null;
        }

        if (! class_exists($collectionClass) || ! is_subclass_of($collectionClass, AssetCollectionDefinitionInterface::class)) {
            throw new InvalidArgumentException("Collection class '{$collectionClass}' does not exist or does not implement AssetCollectionDefinitionInterface.");
        }

        return $collectionClass;
    }

    /**
     * Get the asset collection definition for this asset
     *
     * @param mixed ...$definitionArguments Additional arguments to pass to the collection definition constructor
     *
     * @return AssetCollectionDefinitionInterface|null The asset collection definition, or null if not set
     */
    public function getAssetCollectionDefinition(...$definitionArguments): ?AssetCollectionDefinitionInterface
    {
        $collectionClass = $this->getAssetCollectionDefinitionClass();

        if ($collectionClass === null) {
            return null;
        }

        return AssetCollectionDefinitionFactory::create($collectionClass, ...$definitionArguments);
    }
}
