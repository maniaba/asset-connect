<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Asset;

use CodeIgniter\Entity\Entity;
use CodeIgniter\Events\Events;
use CodeIgniter\Files\File;
use CodeIgniter\I18n\Time;
use Maniaba\AssetConnect\AssetCollection\AssetCollection;
use Maniaba\AssetConnect\AssetCollection\SetupAssetCollection;
use Maniaba\AssetConnect\AssetConnect;
use Maniaba\AssetConnect\AssetVariants\AssetVariants;
use Maniaba\AssetConnect\AssetVariants\AssetVariantsProcess;
use Maniaba\AssetConnect\AssetVariants\Interfaces\AssetVariantsInterface;
use Maniaba\AssetConnect\Events\AssetCreated;
use Maniaba\AssetConnect\Exceptions\AssetException;
use Maniaba\AssetConnect\Exceptions\FileException;
use Maniaba\AssetConnect\Exceptions\InvalidArgumentException;
use Maniaba\AssetConnect\Models\AssetModel;
use Maniaba\AssetConnect\PathGenerator\PathGenerator;
use Maniaba\AssetConnect\PathGenerator\PathGeneratorFactory;
use Maniaba\AssetConnect\Storage\Interfaces\StorageDiskInterface;
use Maniaba\AssetConnect\Storage\StorageManager;
use Throwable;

final class AssetPersistenceManager
{
    private readonly PathGenerator $pathGenerator;
    private readonly AssetCollection $collection;
    private AssetVariants $assetVariants;
    private ?StorageDiskInterface $storageDisk = null;
    private ?string $storedPath                = null;
    private bool $copyInsertAttempted          = false;

    /**
     * @var list<string>
     */
    private array $copyVariantPaths = [];

    public function __construct(
        private readonly ?Entity $subjectEntity,
        private Asset $asset,
        private readonly SetupAssetCollection $setupAssetCollection,
        private readonly ?string $copyFileName = null,
    ) {
        $this->collection = AssetCollection::create($this->setupAssetCollection);

        // Set the collection name using the setCollection method
        $this->asset->setCollection($this->setupAssetCollection->getCollectionDefinition());

        $this->pathGenerator = PathGeneratorFactory::create($this->collection);
    }

    /**
     * Store the asset in the configured storage disk.
     *
     * @return Asset The stored asset
     *
     * @throws AssetException|FileException
     * @throws Throwable
     */
    public function store(): Asset
    {
        try {
            // Validate the asset against the collection definition
            $this->validateAsset();

            // Store the file
            $this->storeFile();

            // Process file variants if the collection implements FileVariantInterface
            $this->processFileVariants();

            // Save the asset to the database
            $this->insertAsset(AssetModel::init(false));
            $this->scheduleVariants();
            $this->publish();

            // Check if we need to enforce maximum number of items in collection
            $this->enforceMaximumNumberOfItemsInCollection();
        } catch (Throwable $exception) {
            // Clean up any garbage if the storage fails
            $this->cleanGarbage();

            // Continue to throw the original exception
            throw $exception;
        }

        return $this->asset;
    }

    /**
     * @internal
     */
    public function prepareCopy(StorageDiskInterface $disk, string $storage): void
    {
        $this->setCopyStorage($disk, $storage);
        $this->asset->path = $this->pathGenerator->getPath() . $this->copyFileName;
        $this->validateAsset();
    }

    private function setCopyStorage(StorageDiskInterface $disk, string $storage): void
    {
        $requiredStorage = $this->collection->getStorage();

        if (($requiredStorage !== null && $requiredStorage !== $storage)
            || $disk->visibility() !== $this->collection->getVisibility()
        ) {
            $message = "Storage disk '{$storage}' is not allowed by the destination collection.";

            throw new AssetException($message, $message, 400);
        }

        $this->asset->storage = $storage;
        $this->storageDisk    = $disk;
    }

    /**
     * @internal
     */
    public function prepareQueuedCopy(string $batch): string
    {
        $this->asset->setInternalProperty(Asset::COPY_BATCH_PROPERTY, $batch);

        return $this->pathGenerator->getPathForVariants();
    }

    /**
     * @internal
     */
    public function stageCopy(Asset $source, ?string $queuedVariantDirectory = null): void
    {
        $sourceDisk = $source->getStorageDisk();
        if (! $sourceDisk->fileExists($source->path)) {
            throw FileException::forFileNotFound($source->path);
        }

        $disk = $this->storageDisk ?? throw new InvalidArgumentException('Destination storage has not been set.');
        $path = $this->asset->path;

        if ($disk->fileExists($path)) {
            throw FileException::forCannotCopyFile($source->path, $path);
        }

        $sourceSize = $sourceDisk->fileSize($source->path);
        if ($queuedVariantDirectory === null) {
            $this->asset->size = $sourceSize;
            $this->validateAsset();
        } elseif ($sourceSize !== $this->asset->size) {
            throw FileException::forCannotCopyFile($source->path, $path);
        }

        $this->writeFile($disk, $this->asset->storage, $path, $sourceDisk->readStream($source->path), [
            'mimetype' => $this->asset->mime_type,
        ]);

        if ($disk->fileSize($path) !== $sourceSize) {
            throw FileException::forCannotCopyFile($source->path, $path);
        }

        $this->processFileVariants($queuedVariantDirectory);
        unset($this->asset->file);
    }

    /**
     * @internal
     */
    public function discardCopy(): void
    {
        // Remove the row first so workers cannot start processing its variants.
        try {
            if ($this->copyInsertAttempted) {
                $model = AssetModel::init(false);
                $model->where([
                    'path'    => $this->asset->path,
                    'storage' => $this->asset->storage,
                ]);
                if ($this->asset->id <= 0 && $this->asset->isCopyPending()) {
                    $model->where('metadata', json_encode($this->asset->metadata, JSON_THROW_ON_ERROR));
                }
                $deleted = $model->delete($this->asset->id ?: null, true);
                if (! $deleted) {
                    throw AssetException::forDatabaseError($model->errors() ?: ['Could not remove the copied asset.']);
                }
            }

            if ($this->asset->id > 0) {
                $this->connection()?->removeAssetById($this->asset->id);
            }
        } catch (Throwable $exception) {
            log_message('error', 'Failed to roll back copied asset row: {message}', ['message' => $exception->getMessage()]);
        }

        $this->discardCopyFiles();
    }

    /**
     * @internal
     */
    public function discardCopyFiles(): void
    {
        foreach ($this->copyVariantPaths as $path) {
            $this->cleanStoragePath($path);
        }

        if ($this->storedPath !== null) {
            $this->cleanStoragePath($this->storedPath);
        }
    }

    /**
     * Validate the asset against the collection definition
     *
     * @throws AssetException
     */
    private function validateAsset(): void
    {
        // Check file size
        $maxFileSize = $this->collection->getMaxFileSize();
        if ($maxFileSize > 0 && $this->asset->size > $maxFileSize) {
            throw AssetException::forFileTooLarge($this->asset->size, $maxFileSize);
        }

        // Check file extension
        $allowedExtensions = $this->collection->getAllowedExtensions();
        if ($allowedExtensions !== []) {
            $extension = $this->asset->extension;

            if (! in_array(strtolower($extension), $allowedExtensions, true)) {
                throw AssetException::forInvalidFileExtension($extension, $allowedExtensions);
            }
        }

        // Check MIME type
        $allowedMimeTypes = $this->collection->getAllowedMimeTypes();
        if ($allowedMimeTypes !== [] && ! in_array($this->asset->mime_type, $allowedMimeTypes, true)) {
            throw AssetException::forInvalidMimeType($this->asset->mime_type, $allowedMimeTypes);
        }
    }

    /**
     * Store the file
     *
     * @throws FileException
     */
    private function storeFile(): void
    {
        $file = $this->asset->file;

        if (! $file instanceof File) {
            throw new InvalidArgumentException('Unsupported asset type for storage.');
        }

        $sourcePath = $file->getRealPath();
        if (! is_string($sourcePath) || $sourcePath === '' || ! file_exists($sourcePath)) {
            throw FileException::forFileNotFound((string) $sourcePath);
        }

        $storageManager = StorageManager::make();
        $storageName    = $this->collection->getStorage()
            ?? $storageManager->defaultDiskNameForVisibility($this->collection->getVisibility());

        $disk = $storageManager->disk($storageName);

        $relativeDirectory = $this->pathGenerator->getPath();
        $relativePath      = $relativeDirectory . $this->asset->file_name;

        $stream = fopen($sourcePath, 'rb');
        if ($stream === false) {
            throw FileException::forFileNotFound($sourcePath);
        }

        $this->writeFile($disk, $storageName, $relativePath, $stream);
    }

    /**
     * @param resource $stream
     */
    private function writeFile(StorageDiskInterface $disk, string $storageName, string $relativePath, $stream, array $config = []): void
    {
        $this->storageDisk    = $disk;
        $this->storedPath     = $relativePath;
        $this->asset->storage = $storageName;
        $this->asset->path    = $relativePath;

        try {
            $disk->writeStream($relativePath, $stream, $config + [
                'visibility' => $this->collection->getVisibility()->value,
            ]);
        } catch (Throwable $exception) {
            throw FileException::forCannotWriteToStorage($storageName, $relativePath, $exception);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        $localPath = $disk->localPath($relativePath);
        if ($localPath !== null) {
            $this->asset->file = new File($localPath);
        }
    }

    /**
     * @internal
     */
    public function insertAsset(AssetModel $model): void
    {
        $this->copyInsertAttempted = $this->copyFileName !== null;
        if (! $model->save($this->asset)) {
            throw AssetException::forDatabaseError($model->errors() ?: ['Could not insert asset.']);
        }

        $this->asset->id = $model->getInsertID();

        $this->asset->created_at = Time::now();
        $this->asset->updated_at = Time::now();
    }

    /**
     * @internal
     */
    public function scheduleVariants(): void
    {
        // If variants are processed on the queue, we must add queue job for processing
        if (isset($this->assetVariants) && $this->assetVariants->onQueue) {
            AssetVariantsProcess::onQueue(
                $this->asset,
                $this->setupAssetCollection->getCollectionDefinition(),
            );
        }
    }

    /**
     * @internal
     */
    public function publish(): void
    {
        unset($this->asset->file); // Unset the file property unless you need it later

        // If the asset was saved, we can now connect it to the entity
        $this->connection()?->addAsset($this->asset);

        // Trigger asset.created event
        if ($this->subjectEntity !== null) {
            Events::trigger(AssetCreated::name(), AssetCreated::createFromAsset($this->asset, $this->subjectEntity));
        }
    }

    private function connection(): ?AssetConnect
    {
        if ($this->subjectEntity === null || ! method_exists($this->subjectEntity, 'assetConnectInstance')) {
            return null;
        }

        $connection = $this->subjectEntity->assetConnectInstance();

        return $connection instanceof AssetConnect ? $connection : null;
    }

    /**
     * Process file variants if the collection implements FileVariantInterface
     */
    private function processFileVariants(?string $queuedVariantDirectory = null): void
    {
        $definition = $this->setupAssetCollection->getCollectionDefinition();

        if (! $definition instanceof AssetVariantsInterface) {
            return;
        }

        $this->assetVariants = new AssetVariants(
            $this->pathGenerator,
            $this->asset,
            $this->copyFileName,
            $queuedVariantDirectory,
        );

        $definition->variants($this->assetVariants, $this->asset);

        if ($this->copyFileName !== null) {
            $disk = $this->asset->getStorageDisk();

            foreach ($this->asset->metadata->assetVariant->getVariants() as $variant) {
                $directory = $queuedVariantDirectory ?? $this->pathGenerator->getPathForVariants();

                if ($variant->storage !== $this->asset->storage
                    || $variant->path !== $directory . basename($variant->path)
                    || $variant->path === $this->storedPath
                    || str_contains($variant->path, '\\')
                    || $disk->fileExists($variant->path)
                    || in_array($variant->path, $this->copyVariantPaths, true)
                ) {
                    throw FileException::forInvalidFile($variant->path);
                }

                $this->copyVariantPaths[] = $variant->path;
            }
        }

        if (! $this->assetVariants->onQueue || $queuedVariantDirectory !== null) {
            // If the definition indicates that variants should be processed immediately,
            AssetVariantsProcess::run($this->asset, $definition);
        }
    }

    /**
     * @internal
     */
    public static function resumeQueuedCopy(Asset $asset): self
    {
        $path  = $asset->path;
        $setup = (new SetupAssetCollection())->setDefaultCollectionDefinition($asset->collection_definition_class);
        $copy  = new self(null, $asset, $setup, basename($path));
        $copy->setCopyStorage($asset->getStorageDisk(), $asset->storage);

        return $copy;
    }

    /**
     * Clean garbage if failed to store the asset any reason
     */
    private function cleanGarbage(): void
    {
        if ($this->storedPath !== null) {
            $this->cleanStoragePath($this->storedPath);
        }

        if ($this->asset->id > 0) {
            // If the asset was saved, delete it from the database
            $model = AssetModel::init(false);
            $model->delete($this->asset->id, true);
        }
    }

    private function cleanStoragePath(string $path): void
    {
        try {
            $this->storageDisk?->delete($path);
        } catch (Throwable $exception) {
            log_message('error', 'Failed to clean up garbage after asset storage failure: {message}', ['message' => $exception->getMessage()]);
        }
    }

    public static function removeStoragePath(string $storage, string $path): void
    {
        StorageManager::make()->disk($storage)->delete($path);
    }

    /**
     * Enforce maximum number of items in collection by deleting oldest assets
     * if the maximum number is exceeded
     */
    private function enforceMaximumNumberOfItemsInCollection(): void
    {
        $ids = $this->obsoleteAssetIds(AssetModel::init(false));
        if ($ids === []) {
            return;
        }

        // Files from storage will be deleted in queue.
        AssetModel::init(false)->whereIn('id', $ids)->delete();

        foreach ($ids as $id) {
            $this->connection()?->removeAssetById($id);
        }
    }

    /** @internal
     * @return list<int>
     */
    public function obsoleteAssetIds(AssetModel $model): array
    {
        // Get the maximum number of items allowed in this collection
        $maxItems = $this->collection->getMaximumNumberOfItemsInCollection();

        if ($maxItems <= 0) {
            return [];
        }

        // Get the AssetModel
        $model->where([
            'collection'  => $this->asset->collection,
            'entity_type' => $this->asset->entity_type,
            'entity_id'   => $this->asset->entity_id,
            'deleted_at'  => null, // Only consider non-deleted assets
        ])->orderBy('created_at', 'DESC');
        if ($this->copyFileName !== null) {
            $model->orderBy('id', 'DESC');
        }

        $ids = $model
            ->limit(2147483647) // Use a large limit to get all assets in the collection, int max is 2147483647 for best compatibility
            ->offset($maxItems) // Skip the newest $maxItems (which we want to keep)
            ->findColumn('id');

        return array_map(intval(...), $ids ?? []);
    }
}
