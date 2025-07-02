<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Asset;

use CodeIgniter\Entity\Entity;
use CodeIgniter\Files\File;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\I18n\Time;
use InvalidArgumentException;
use Maniaba\FileConnect\Asset\Interfaces\AssetCollectionDefinitionInterface;
use Maniaba\FileConnect\Asset\Traits\AssetMimeTypeTrait;
use Maniaba\FileConnect\AssetCollection\AssetCollectionDefinitionFactory;
use Maniaba\FileConnect\Models\AssetModel;
use Maniaba\FileConnect\UrlGenerator\Traits\UrlGeneratorTrait;

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
 * @property-read AssetMetadata     $metadata
 * @property      string            $mime_type    MIME type of the file
 * @property      string            $name         name of the asset
 * @property      int               $order        order of the asset in the collection
 * @property      string            $path         path to the file on the server
 * @property-read string            $path_dirname directory path of the file on the server
 * @property      int               $size         size of the file in bytes
 * @property      Time              $updated_at   timestamp when the asset was last updated
 */
final class Asset extends Entity
{
    use AssetMimeTypeTrait;
    use UrlGeneratorTrait;

    protected $casts = [
        'id'          => 'int',
        'entity_type' => 'string',
        'entity_id'   => 'int',
        'order'       => 'int',
        'collection'  => 'string',
        'size'        => 'int',
    ];
    private AssetMetadata $metadata;

    public function setEntityType(Entity|string $entityType): static
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

        $this->getMetadata()->basicInfo->setCollectionClass($collection);

        $this->attributes['collection'] = md5($collection);

        return $this;
    }

    protected function setMetadata(AssetMetadata|string|null $metadata): static
    {
        if (is_string($metadata)) {
            $metadata = new AssetMetadata(json_decode($metadata, true));
        } elseif ($metadata === null) {
            $metadata = new AssetMetadata();
        }

        $this->metadata = $metadata;

        return $this;
    }

    protected function getMetadata(): AssetMetadata
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

    public function toRawArray(bool $onlyChanged = false, bool $recursive = false): array
    {
        $rawArray             = parent::toRawArray($onlyChanged, $recursive);
        $rawArray['metadata'] = json_encode($this->getMetadata());

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
        $data = new Asset([
            'id'         => $this->id,
            'metadata'   => $this->getMetadata(),
            'name'       => $this->name,
            'order'      => $this->order,
            'updated_at' => $this->updated_at,
        ]);

        $model = model(AssetModel::class, false);

        return $model->save($data);
    }

    protected function mimeTypeValue(): string
    {
        return $this->mime_type;
    }
}
