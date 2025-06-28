<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Asset;

use CodeIgniter\Entity\Entity;
use CodeIgniter\Files\File;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\I18n\Time;
use InvalidArgumentException;
use Maniaba\FileConnect\Interfaces\Asset\AssetCollectionInterface;

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
        } elseif (! class_exists($entityType) || ! is_subclass_of($entityType, Entity::class)) {
            throw new InvalidArgumentException('Entity type must be a valid Entity class or instance.');
        }

        $this->attributes['entity_type'] = md5($entityType);

        return $this;
    }

    public function setCollection(AssetCollectionInterface|string $collection): static
    {
        if ($collection instanceof AssetCollectionInterface) {
            $collection = $collection::class;
        } elseif (! class_exists($collection) || ! is_subclass_of($collection, AssetCollectionInterface::class)) {
            throw new InvalidArgumentException('Collection must be a valid AssetCollectionInterface class or instance.');
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

        return new Properties(json_decode($value, true));
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
}
