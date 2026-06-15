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
use Maniaba\AssetConnect\AssetVariants\AssetVariant;
use Maniaba\AssetConnect\Config\Asset as AssetConfig;
use Maniaba\AssetConnect\Enums\AssetVisibility;
use Maniaba\AssetConnect\Events\AssetUpdated;
use Maniaba\AssetConnect\Exceptions\AssetException;
use Maniaba\AssetConnect\Exceptions\FileException;
use Maniaba\AssetConnect\Models\AssetModel;
use Maniaba\AssetConnect\Services\AssetAccessService;
use Maniaba\AssetConnect\Storage\Interfaces\StorageDiskInterface;
use Maniaba\AssetConnect\Storage\StorageManager;
use Maniaba\AssetConnect\Storage\TemporaryStorageFile;
use Maniaba\AssetConnect\UrlGenerator\Traits\UrlGeneratorTrait;
use Maniaba\AssetConnect\Utils\Format;
use Override;
use Throwable;

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
 * @property      string                                           $path                        relative path to the file in the configured storage disk
 * @property-read string|null                                      $local_path                  local filesystem path if the configured storage disk is local
 * @property-read AssetMetadata                                    $metadata
 * @property-read string                                           $path_dirname                relative directory path of the file in the configured storage disk
 * @property-read string                                           $relative_path               relative path of the file in the storage disk
 * @property      int                                              $size                        size of the file in bytes
 * @property-read string                                           $relative_path_for_url       relative path of the file in the storage
 * @property      string                                           $storage                     configured storage disk name
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
        'storage'     => 'string',
        'size'        => 'int',
    ];
    private AssetMetadata $metadata;

    protected function getEntityTypeClassName(): string
    {
        /** @var AssetConfig $config */
        $config = config('Asset');

        return $config->getEntityClassFromKey($this->entity_type);
    }

    final public function setEntityType(Entity|string $entityType): static
    {
        /** @var AssetConfig $config */
        $config = config('Asset');

        $this->attributes['entity_type'] = $config->getEntityTypeKey($entityType);

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

        $this->attributes['collection'] = $config->getCollectionKey($collection);

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

        // if not exists key size, storage, path or mime_type, we need to add them by calling their getters
        $requiredKeys    = ['size', 'storage', 'path', 'mime_type'];
        $isUpdateRequest = $this->id !== null && $this->id > 0;

        if (! $isUpdateRequest) {
            foreach ($requiredKeys as $key) {
                if (! array_key_exists($key, $rawArray)) {
                    $rawArray[$key] = $this->{$key};
                }
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
        // The stored file name is the final name after AssetAdder configuration.
        $fileName = $this->attributes['file_name'] ?? null;
        if (is_string($fileName) && $fileName !== '') {
            return pathinfo($fileName, PATHINFO_EXTENSION);
        }

        $path = $this->attributes['path'] ?? null;
        if (is_string($path) && $path !== '') {
            return pathinfo($path, PATHINFO_EXTENSION);
        }

        if (isset($this->file) && $this->file instanceof File) {
            return $this->file->getExtension();
        }

        throw new \Maniaba\AssetConnect\Exceptions\InvalidArgumentException('Invalid argument provided');
    }

    protected function getPathDirname(): string
    {
        if ($this->path === null || $this->path === '') {
            throw new \Maniaba\AssetConnect\Exceptions\InvalidArgumentException('Path directory not set.');
        }

        return rtrim(str_replace('\\', '/', dirname($this->path)), '/') . '/';
    }

    protected function getLocalPath(): ?string
    {
        return $this->getStorageDisk()->localPath($this->path);
    }

    public function getStorageDisk(): StorageDiskInterface
    {
        return StorageManager::make()->disk($this->storage);
    }

    public function copyToTemporaryFile(?string $variantName = null, ?string $directory = null, string $prefix = 'asset_connect_'): string
    {
        if ($variantName !== null && $variantName !== '' && $variantName !== '0') {
            $variant = $this->getMetadata()->assetVariant->getAssetVariant($variantName);

            if (! $variant instanceof AssetVariant) {
                throw new \Maniaba\AssetConnect\Exceptions\InvalidArgumentException("Variant '{$variantName}' does not exist for asset '{$this->id}'.");
            }

            $storage = $variant->storage !== '' ? $variant->storage : $this->storage;

            return TemporaryStorageFile::copyFromStorage(
                StorageManager::make()->disk($storage),
                $variant->path,
                $variant->extension,
                $directory,
                $prefix,
            );
        }

        return TemporaryStorageFile::copyFromStorage(
            $this->getStorageDisk(),
            $this->path,
            $this->extension,
            $directory,
            $prefix,
        );
    }

    /**
     * @template TReturn
     *
     * @param callable(string): TReturn $callback
     *
     * @return TReturn
     */
    public function withTemporaryFile(
        callable $callback,
        ?string $variantName = null,
        ?string $directory = null,
        string $prefix = 'asset_connect_',
    ): mixed {
        $temporaryFile = $this->copyToTemporaryFile($variantName, $directory, $prefix);

        try {
            return $callback($temporaryFile);
        } finally {
            if (is_file($temporaryFile)) {
                @unlink($temporaryFile);
            }
        }
    }

    /**
     * Get the class name of the asset collection definition for this asset
     *
     * @return string The class name of the asset collection definition
     *
     * @throws InvalidArgumentException If the collection class does not exist or does not implement AssetCollectionDefinitionInterface
     */
    public function getCollectionDefinitionClass(): string
    {
        /** @var AssetConfig $config */
        $config = config('Asset');

        return $config->getCollectionClassFromKey($this->collection);
    }

    /**
     * Check if the asset is stored on a protected disk.
     *
     * Before the asset has a storage disk, authorizable collections still
     * default to protected visibility.
     *
     * @return bool True if the collection is protected, false otherwise.
     */
    protected function getIsProtectedCollection(): bool
    {
        $storage = $this->attributes['storage'] ?? null;

        if (is_string($storage) && trim($storage) !== '') {
            return StorageManager::make()->disk($storage)->visibility() === AssetVisibility::PROTECTED;
        }

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
     * @return class-string<Entity> The class name of the subject entity
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
        $this->getMetadata()->userCustom->set($propertyName, $value);

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

    public function getInternalProperty(string $propertyName): mixed
    {
        return $this->getMetadata()->internal->get($propertyName);
    }

    public function setInternalProperty(string $propertyName, mixed $value): static
    {
        $this->getMetadata()->internal->set($propertyName, $value);

        return $this;
    }

    /**
     * Get all internal properties.
     *
     * Internal properties are intended for application/backend metadata and
     * are not included in the public asset JSON representation.
     *
     * @return array<string, mixed>
     */
    public function getInternalProperties(): array
    {
        return $this->getMetadata()->internal->getAll();
    }

    /**
     * Transfer the asset file to another configured storage disk.
     *
     * The storage-relative path stays the same. Existing variants are moved
     * with the asset by default, and unprocessed variants have only their
     * metadata storage updated so queued processors write them to the new disk.
     */
    public function transferToStorage(string $storage, bool $withVariants = true, bool $deleteSource = true): static
    {
        $targetStorage = trim($storage);
        if ($targetStorage === '') {
            throw new \Maniaba\AssetConnect\Exceptions\InvalidArgumentException('Target storage disk name must not be empty.');
        }

        $storageManager = StorageManager::make();
        $targetDisk     = $storageManager->disk($targetStorage);
        $sourceStorage  = $this->storage;
        $sourcePath     = $this->path;

        /** @var list<array{disk: StorageDiskInterface, path: string}> $copiedTargets */
        $copiedTargets = [];
        /** @var list<array{disk: StorageDiskInterface, path: string}> $deleteSources */
        $deleteSources = [];
        /** @var array<string, string> $variantSourceStorages */
        $variantSourceStorages = [];

        try {
            if ($sourceStorage !== $targetStorage) {
                $sourceDisk = $storageManager->disk($sourceStorage);
                $this->copyStoragePath($sourceDisk, $targetDisk, $sourcePath);

                $copiedTargets[] = ['disk' => $targetDisk, 'path' => $sourcePath];
                $deleteSources[] = ['disk' => $sourceDisk, 'path' => $sourcePath];
            }

            if ($withVariants) {
                foreach ($this->getMetadata()->assetVariant->getVariants() as $variantName => $variant) {
                    $variantSourceStorages[$variantName] = $variant->storage;
                    $variantSourceStorage                = $variant->storage !== '' ? $variant->storage : $sourceStorage;

                    if ($variantSourceStorage !== $targetStorage) {
                        $variantSourceDisk = $storageManager->disk($variantSourceStorage);

                        if ($variantSourceDisk->fileExists($variant->path)) {
                            $this->copyStoragePath($variantSourceDisk, $targetDisk, $variant->path);

                            $copiedTargets[] = ['disk' => $targetDisk, 'path' => $variant->path];
                            $deleteSources[] = ['disk' => $variantSourceDisk, 'path' => $variant->path];
                        } elseif ($variant->processed) {
                            throw FileException::forFileNotFound($variantSourceDisk->name() . ':' . $variant->path);
                        }
                    }

                    $variant->storage = $targetStorage;
                    $this->getMetadata()->assetVariant->updateAssetVariant($variant);
                }
            }

            $this->storage = $targetStorage;
            $this->persistStorageTransfer();
        } catch (Throwable $exception) {
            $this->storage = $sourceStorage;
            $this->restoreVariantStorages($variantSourceStorages);
            $this->deleteStoragePaths($copiedTargets);

            throw $exception;
        }

        if ($deleteSource) {
            $this->deleteStoragePaths($deleteSources);
        }

        return $this;
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

    private function copyStoragePath(StorageDiskInterface $sourceDisk, StorageDiskInterface $targetDisk, string $path): void
    {
        if (! $sourceDisk->fileExists($path)) {
            throw FileException::forFileNotFound($sourceDisk->name() . ':' . $path);
        }

        if ($targetDisk->fileExists($path)) {
            throw FileException::forCannotCopyFile($sourceDisk->name() . ':' . $path, $targetDisk->name() . ':' . $path);
        }

        $stream = $sourceDisk->readStream($path);

        try {
            $targetDisk->writeStream($path, $stream, [
                'visibility' => $targetDisk->visibility()->value,
            ]);
        } catch (Throwable $exception) {
            throw FileException::forCannotWriteToStorage($targetDisk->name(), $path, $exception);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    private function persistStorageTransfer(): void
    {
        $this->updated_at = Time::now();

        $model  = AssetModel::init(false);
        $result = $model->save(Asset::create([
            'id'         => $this->id,
            'storage'    => $this->storage,
            'path'       => $this->path,
            'metadata'   => $this->getMetadata(),
            'updated_at' => $this->updated_at,
        ]));

        if (! $result) {
            throw AssetException::forDatabaseError($model->errors() ?: ['Unable to update asset storage.']);
        }

        Events::trigger(AssetUpdated::name(), AssetUpdated::createFromId($this->id));
    }

    /**
     * @param array<string, string> $variantSourceStorages
     */
    private function restoreVariantStorages(array $variantSourceStorages): void
    {
        foreach ($variantSourceStorages as $variantName => $storage) {
            $variant = $this->getMetadata()->assetVariant->getAssetVariant($variantName);

            if (! $variant instanceof AssetVariant) {
                continue;
            }

            $variant->storage = $storage;
            $this->getMetadata()->assetVariant->updateAssetVariant($variant);
        }
    }

    /**
     * @param list<array{disk: StorageDiskInterface, path: string}> $paths
     */
    private function deleteStoragePaths(array $paths): void
    {
        foreach ($paths as $path) {
            try {
                $path['disk']->delete($path['path']);
            } catch (Throwable $exception) {
                log_message('error', 'Failed to delete storage path after asset transfer: {message}', [
                    'message' => $exception->getMessage(),
                ]);
            }
        }
    }

    #[Override]
    protected function mimeTypeValue(): string
    {
        return $this->mime_type;
    }

    protected function getRelativePath(): string
    {
        $relativePath = $this->path;

        if (! is_string($relativePath) || $relativePath === '') {
            throw new \Maniaba\AssetConnect\Exceptions\InvalidArgumentException('File relative path not set.');
        }

        $relativePath = str_replace('\\', '/', $relativePath);

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
