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
use Maniaba\AssetConnect\Asset\Interfaces\AuthorizableAssetCollectionDefinitionInterface;
use Maniaba\AssetConnect\Asset\Traits\AssetFileInfoTrait;
use Maniaba\AssetConnect\Asset\Traits\AssetMimeTypeTrait;
use Maniaba\AssetConnect\AssetCollection\AssetCollectionDefinitionFactory;
use Maniaba\AssetConnect\Config\Asset as AssetConfig;
use Maniaba\AssetConnect\Events\AssetUpdated;
use Maniaba\AssetConnect\Models\AssetModel;
use Maniaba\AssetConnect\Services\AssetAccessService;
use Maniaba\AssetConnect\UrlGenerator\Traits\UrlGeneratorTrait;
use Maniaba\AssetConnect\Utils\Format;
use Override;

/**
 * @property      string                                           $collection                  name of the collection to which the asset belongs (md5 hash of the class name)
 * @property-read class-string<AssetCollectionDefinitionInterface> $collection_definition_class class name of the collection definition to which the asset belongs
 * @property      Time                                             $created_at                  timestamp when the asset was created
 * @property      Time|null                                        $deleted_at                  timestamp when the asset was deleted, null if not deleted
 * @property      int                                              $entity_id                   identifier for the entity to which the asset belongs
 * @property      string                                           $entity_type                 type of the entity to which the asset belongs(md5 hash of the class name)
 * @property-read string                                           $extension                   file extension of the asset
 * @property      File|UploadedFile|null                           $file                        file object associated with the asset, null if not set
 * @property      string                                           $file_name                   name of the file associated with the asset
 * @property-read string                                           $format_human_readable_size  human-readable format of the file size
 * @property      int                                              $id                          identifier for the asset
 * @property-read bool                                             $is_protected_collection     indicates if the asset belongs to a protected collection
 * @property      string                                           $mime_type                   MIME type of the file
 * @property      string                                           $name                        name of the asset
 * @property      int                                              $order                       order of the asset in the collection
 * @property      string                                           $path                        path to the file on the server
 * @property-read AssetMetadata                                    $metadata
 * @property-read string                                           $path_dirname                directory path of the file on the server
 * @property-read string                                           $relative_path               relative path of the file in the storage
 * @property      int                                              $size                        size of the file in bytes
 * @property-read string                                           $relative_path_for_url       relative path of the file in the storage
 * @property-read class-string<Entity>                             $subject_entity_class        class name of the entity to which the asset belongs
 * @property      Time                                             $updated_at                  timestamp when the asset was last updated
 * @property-read string                                           $url                         URL to access the asset
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

    protected function getEntityTypeClassName(): string
    {
        /** @var AssetConfig $config */
        $config      = config('Asset');
        $entityClass = array_search($this->entity_type, $config->entityKeyDefinitions, true);

        if ($entityClass === false) {
            throw new InvalidArgumentException("Entity class for entity type '{$this->entity_type}' is not registered in asset entity definitions.");
        }

        return $entityClass;
    }

    final public function setEntityType(Entity|string $entityType): static
    {
        /** @var AssetConfig $config */
        $config = config('Asset');

        if ($entityType instanceof Entity || class_exists($entityType)) {
            $entityType = is_string($entityType) ? $entityType : $entityType::class;

            $entityKey = $config->entityKeyDefinitions[$entityType] ?? null;

            if ($entityKey === null) {
                throw new InvalidArgumentException("Entity key for entity class '{$entityType}' is not registered in asset entity definitions.");
            }

            $this->attributes['entity_type'] = $entityKey;

            return $this;
        }

        // search entity type key from config
        $entityKey = array_search($entityType, $config->entityKeyDefinitions, true);
        if ($entityKey === false) {
            throw new InvalidArgumentException("Entity class '{$entityType}' is not registered in asset entity definitions.");
        }

        $this->attributes['entity_type'] = $entityType;

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
        /** @var AssetConfig $config */
        $config = config('Asset');

        if ($collection instanceof AssetCollectionDefinitionInterface || (class_exists($collection) && is_subclass_of($collection, AssetCollectionDefinitionInterface::class))) {
            $collection = is_string($collection) ? $collection : $collection::class;
            AssetCollectionDefinitionFactory::validateStringClass($collection);

            $collectionKey = $config->collectionKeyDefinitions[$collection] ?? null;

            if ($collectionKey === null) {
                throw new InvalidArgumentException("Collection key for collection class '{$collection}' is not registered in asset collection definitions.");
            }

            $this->attributes['collection'] = $collectionKey;

            return $this;
        }

        // search collection key from config
        $collectionKey = in_array($collection, $config->collectionKeyDefinitions, true);
        if ($collectionKey === false) {
            throw new InvalidArgumentException("Collection class '{$collection}' is not registered in asset collection definitions.");
        }

        $this->attributes['collection'] = $collection;

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

        // if not exists key size, path or mime_type, we need to add them by calling their getters
        $requiredKeys = ['size', 'path', 'mime_type'];

        foreach ($requiredKeys as $key) {
            if (! array_key_exists($key, $rawArray)) {
                $rawArray[$key] = $this->{$key};
            }
        }

        return $rawArray;
    }

    protected function getSize(): int
    {
        // For Asset (Entity): Try file first, then fallback to stored attribute
        if (isset($this->file)) {
            return $this->file->getSize() ?? 0;
        }

        // Fallback to stored size in attributes
        return (int) ($this->attributes['size'] ?? 0);
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
    public function getCollectionDefinitionClass(): string
    {
        /** @var AssetConfig $config */
        $config          = config('Asset');
        $collectionClass = array_search($this->collection, $config->collectionKeyDefinitions, true);

        if ($collectionClass === false) {
            throw new InvalidArgumentException("Collection class '{$this->collection}' is not registered in asset collection definitions.");
        }

        return $collectionClass;
    }

    /**
     * Check if the collection is protected.
     *
     * A collection is considered protected if it implements the AuthorizableAssetCollectionDefinitionInterface.
     *
     * @return bool True if the collection is protected, false otherwise.
     */
    protected function getIsProtectedCollection(): bool
    {
        return is_subclass_of($this->collection_definition_class, AuthorizableAssetCollectionDefinitionInterface::class);
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
        return AssetCollectionDefinitionFactory::create($this->getCollectionDefinitionClass(), ...$definitionArguments);
    }

    /**
     * Get the subject entity which this asset belongs to.
     *
     * * @return Entity|null The entity that this asset belongs to, or null if not set
     */
    public function getSubjectEntity(...$arguments): ?Entity
    {
        $entityClass = $this->getSubjectEntityClass();

        return new $entityClass(...$arguments);
    }

    /**
     * Get the subject entity which this asset belongs to class name.
     *
     * @return class-string<Entity> The class name of the subject entity, or null if not set
     */
    public function getSubjectEntityClass(): string
    {
        /** @var AssetConfig $config */
        $config    = config('Asset');
        $entityKey = $this->entity_type;

        $entityClass = array_search($entityKey, $config->entityKeyDefinitions, true);

        if ($entityClass === false) {
            throw new InvalidArgumentException("Entity class for entity type '{$entityKey}' is not registered in asset entity definitions.");
        }

        return $entityClass;
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
        $relativePath = $this->getMetadata()->storage->fileRelativePath();

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
        // need to hide a file path on storage
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
