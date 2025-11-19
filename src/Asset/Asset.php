<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Asset;

use CodeIgniter\Entity\Entity;
use CodeIgniter\Events\Events;
use CodeIgniter\Files\File;
use CodeIgniter\HTTP\DownloadResponse;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\I18n\Time;
use InvalidArgumentException;
use JsonSerializable;
use Maniaba\AssetConnect\Asset\Interfaces\AssetCollectionDefinitionInterface;
use Maniaba\AssetConnect\Asset\Traits\AssetFileInfoTrait;
use Maniaba\AssetConnect\Asset\Traits\AssetMimeTypeTrait;
use Maniaba\AssetConnect\AssetCollection\AssetCollectionDefinitionFactory;
use Maniaba\AssetConnect\Events\AssetUpdated;
use Maniaba\AssetConnect\Models\AssetModel;
use Maniaba\AssetConnect\Services\AssetAccessService;
use Maniaba\AssetConnect\UrlGenerator\Traits\UrlGeneratorTrait;
use Maniaba\AssetConnect\Utils\Format;
use Override;

/**
 * @property      string            $collection                 name of the collection to which the asset belongs (md5 hash of the class name)
 * @property      Time              $created_at                 timestamp when the asset was created
 * @property      Time|null         $deleted_at                 timestamp when the asset was deleted, null if not deleted
 * @property      int               $entity_id                  identifier for the entity to which the asset belongs
 * @property      string            $entity_type                type of the entity to which the asset belongs(md5 hash of the class name)
 * @property      File|UploadedFile $file                       file object associated with the asset, null if not set
 * @property      string            $file_name                  name of the file associated with the asset
 * @property-read string            $extension                  file extension of the asset
 * @property      int               $id                         identifier for the asset
 * @property-read string            $format_human_readable_size human-readable format of the file size
 * @property      string            $mime_type                  MIME type of the file
 * @property      string            $name                       name of the asset
 * @property      int               $order                      order of the asset in the collection
 * @property      string            $path                       path to the file on the server
 * @property-read AssetMetadata     $metadata
 * @property-read string            $path_dirname               directory path of the file on the server
 * @property-read string            $relative_path              relative path of the file in the storage
 * @property      int               $size                       size of the file in bytes
 * @property-read string            $relative_path_for_url      relative path of the file in the storage
 * @property-read string            $url                        URL to access the asset
 * @property      Time              $updated_at                 timestamp when the asset was last updated
 */
class Asset extends Entity implements JsonSerializable
{
    use AssetMimeTypeTrait;
    use UrlGeneratorTrait;
    use AssetFileInfoTrait;

    protected $casts = [
        'id'          => 'int',
        'entity_type' => 'string',
        'entity_id'   => 'int',
        'order'       => 'int',
        'collection'  => 'string',
        'size'        => 'int',
    ];
    private AssetMetadata $metadata;

    final public function setEntityType(Entity|string $entityType): static
    {
        if ($entityType instanceof Entity) {
            $entityType = $entityType::class;
        } elseif (! class_exists($entityType) || ($entityType !== Entity::class && ! is_subclass_of($entityType, Entity::class))) {
            throw new InvalidArgumentException('Entity type must be a valid Entity class or instance.');
        }

        $this->getMetadata()->basicInfo->setEntityTypeClass($entityType);

        $this->attributes['entity_type'] = md5($entityType);

        return $this;
    }

    final protected function getRelativePathForUrl(): string
    {
        $relativePath = $this->getRelativePath();

        // Replace backslashes with forward slashes for URL compatibility
        return str_replace('\\', '/', $relativePath);
    }

    /**
     * Set the collection associated with the asset.
     *
     * @param AssetCollectionDefinitionInterface|string $collection The collection to associate with the asset.
     *
     * @throws InvalidArgumentException If $collection is not a valid AssetCollectionDefinitionInterface instance or string
     */
    final public function setCollection(AssetCollectionDefinitionInterface|string $collection): static
    {
        if ($collection instanceof AssetCollectionDefinitionInterface) {
            $collection = $collection::class;
        } else {
            AssetCollectionDefinitionFactory::validateStringClass($collection);
        }

        $this->getMetadata()->basicInfo->setCollectionClass($collection);

        $this->attributes['collection'] = md5($collection);

        return $this;
    }

    final protected function setMetadata(AssetMetadata|string|null $metadata): static
    {
        if (is_string($metadata)) {
            $metadata = new AssetMetadata(json_decode($metadata, true));
        } elseif ($metadata === null) {
            $metadata = new AssetMetadata();
        }

        $this->metadata = $metadata;

        return $this;
    }

    final protected function getMetadata(): AssetMetadata
    {
        if (! isset($this->metadata)) {
            $value = $this->attributes['metadata'] ?? null;

            if (is_string($value)) {
                $value          = json_decode($value, true);
                $this->metadata = new AssetMetadata($value);
            } elseif (is_array($value)) {
                $this->metadata = new AssetMetadata($value);
            } elseif ($value instanceof AssetMetadata) {
                $this->metadata = $value;
            } else {
                $this->metadata = new AssetMetadata();
            }
        }

        return $this->metadata;
    }

    #[Override]
    public function toRawArray(bool $onlyChanged = false, bool $recursive = false): array
    {
        $rawArray             = parent::toRawArray($onlyChanged, $recursive);
        $rawArray['metadata'] = json_encode($this->getMetadata());

        return $rawArray;
    }

    public function getExtension(): string
    {
        // If file is set, we try to get extension from it
        if (isset($this->file) && $this->file instanceof File) {
            return $this->file->getExtension();
        }

        // Otherwise, we check the path attribute
        $path = $this->attributes['path'] ?? null;
        if (is_string($path) && $path !== '') {
            return pathinfo($path, PATHINFO_EXTENSION);
        }

        // If file_name is set, we try to get extension from it
        $fileName = $this->attributes['file_name'] ?? null;
        if (is_string($fileName) && $fileName !== '') {
            return pathinfo($fileName, PATHINFO_EXTENSION);
        }

        throw new \Maniaba\AssetConnect\Exceptions\InvalidArgumentException('Invalid argument provided');
    }

    protected function getPathDirname(): string
    {
        if ($this->path === null || $this->path === '') {
            throw new \Maniaba\AssetConnect\Exceptions\InvalidArgumentException('Path directory not set.');
        }

        return dirname($this->path) . DIRECTORY_SEPARATOR;
    }

    /**
     * Get the class name of the asset collection definition for this asset
     *
     * @return string|null The class name of the asset collection definition, or null if not set
     *
     * @throws InvalidArgumentException If the collection class does not exist or does not implement AssetCollectionDefinitionInterface
     */
    public function getAssetCollectionDefinitionClass(): ?string
    {
        $collectionClass = $this->getMetadata()->basicInfo->collectionClassName();

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

    /**
     * Get the subject entity which this asset belongs to.
     *
     * * @return Entity|null The entity that this asset belongs to, or null if not set
     */
    public function getSubjectEntity(...$arguments): ?Entity
    {
        $entityClass = $this->getSubjectEntityClassName();

        if ($entityClass === null) {
            return null;
        }

        return new $entityClass(...$arguments);
    }

    /**
     * Get the subject entity which this asset belongs to class name.
     *
     * @return string|null The class name of the subject entity, or null if not set
     */
    public function getSubjectEntityClassName(): ?string
    {
        $className = $this->metadata->basicInfo->entityTypeClassName();

        if ($className === null) {
            return null;
        }

        if (! class_exists($className) || ! is_subclass_of($className, Entity::class)) {
            throw new InvalidArgumentException("Entity class '{$className}' does not exist or does not extend Entity.");
        }

        return $className;
    }

    public function getCustomProperty(string $propertyName): mixed
    {
        return $this->getMetadata()->userCustom->get($propertyName);
    }

    public function setCustomProperty(string $propertyName, mixed $value): static
    {
        $this->metadata->userCustom->set($propertyName, $value);

        return $this;
    }

    /**
     * Get all custom properties
     *
     * @return array<string, mixed> An associative array of custom properties
     */
    public function getCustomProperties(): array
    {
        return $this->getMetadata()->userCustom->getAll();
    }

    /**
     * Save the asset to the database.
     *
     * @return bool True if the asset was saved successfully, false otherwise
     */
    public function save(): bool
    {
        $data = Asset::create([
            'id'         => $this->id,
            'metadata'   => $this->getMetadata(),
            'name'       => $this->name,
            'order'      => $this->order,
            'updated_at' => $this->updated_at,
        ]);

        $model  = AssetModel::init(false);
        $result = $model->save($data);

        if ($result) {
            // Trigger asset.updated event
            Events::trigger(AssetUpdated::name(), AssetUpdated::createFromId($this->id));
        }

        return $result;
    }

    #[Override]
    protected function mimeTypeValue(): string
    {
        return $this->mime_type;
    }

    protected function getRelativePath(): string
    {
        $relativePath = $this->getMetadata()->basicInfo->fileRelativePath();

        if ($relativePath === null) {
            throw new \Maniaba\AssetConnect\Exceptions\InvalidArgumentException('File relative path not set.');
        }

        $relativePath = rtrim($relativePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $this->file_name;

        // Ensure the relative path starts with a slash
        if ($relativePath[0] !== '/') {
            $relativePath = '/' . $relativePath;
        }

        return $relativePath;
    }

    public function download(?string $variantName = null): DownloadResponse
    {
        // If variant is not set, return null
        /** @var AssetAccessService $assetAccess */
        $assetAccess = service('assetAccessService');

        return $assetAccess->handleAssetRequest($this->id, $variantName);
    }

    #[Override]
    public function jsonSerialize(): array
    {
        // need to hide file path on storage
        $data = [
            'id'                  => $this->id,
            'entity_id'           => $this->entity_id,
            'name'                => $this->name,
            'file_name'           => $this->file_name,
            'mime_type'           => $this->mime_type,
            'size'                => $this->size,
            'size_human_readable' => $this->getHumanReadableSize(),
            'created_at'          => $this->created_at,
            'updated_at'          => $this->updated_at,
            'deleted_at'          => $this->deleted_at,
            'order'               => $this->order,
            'custom_properties'   => $this->getCustomProperties(),
            'url'                 => $this->getUrl(),
            'url_relative'        => $this->getUrlRelative(),
            'variants'            => [],
        ];

        foreach ($this->getMetadata()->assetVariant->getVariants() as $variant) {
            $data['variants'][$variant->name] = [
                'name'                => $variant->name,
                'size'                => $variant->size,
                'size_human_readable' => Format::formatBytesHumanReadable($variant->size),
                'url'                 => $this->getUrl($variant->name),
                'url_relative'        => $this->getUrlRelative($variant->name),
                'processed'           => $variant->processed,
            ];
        }

        return $data;
    }

    public static function create(?array $data = null): Asset
    {
        $modelReturnType = AssetModel::init(false)->returnType;

        if (! is_subclass_of($modelReturnType, Asset::class) && $modelReturnType !== Asset::class) {
            throw new InvalidArgumentException(
                'Asset model return type must be a subclass of Asset.',
                500,
            );
        }

        return new $modelReturnType($data);
    }
}
