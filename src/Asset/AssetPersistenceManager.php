<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Asset;

use CodeIgniter\Entity\Entity;
use CodeIgniter\Events\Events;
use CodeIgniter\Files\File;
use CodeIgniter\I18n\Time;
use Maniaba\AssetConnect\AssetCollection\AssetCollection;
use Maniaba\AssetConnect\AssetCollection\SetupAssetCollection;
use Maniaba\AssetConnect\AssetVariants\AssetVariants;
use Maniaba\AssetConnect\AssetVariants\AssetVariantsProcess;
use Maniaba\AssetConnect\AssetVariants\Interfaces\AssetVariantsInterface;
use Maniaba\AssetConnect\Contracts\AssetConnectEntityInterface;
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

    public function __construct(
        /**
         * @var AssetConnectEntityInterface&Entity $subjectEntity The entity to which the asset is being added
         */
        private readonly Entity $subjectEntity,
        private Asset $asset,
        private readonly SetupAssetCollection $setupAssetCollection,
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
            $this->saveAsset();

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

        $this->storageDisk = $storageManager->disk($storageName);

        $relativeDirectory = $this->pathGenerator->getPath();
        $relativePath      = $relativeDirectory . $this->asset->file_name;

        $this->storedPath     = $relativePath;
        $this->asset->storage = $storageName;
        $this->asset->path    = $relativePath;

        $stream = fopen($sourcePath, 'rb');
        if ($stream === false) {
            throw FileException::forFileNotFound($sourcePath);
        }

        try {
            $this->storageDisk->writeStream($relativePath, $stream, [
                'visibility' => $this->collection->getVisibility()->value,
            ]);
        } catch (Throwable) {
            throw FileException::forCannotCopyFile($sourcePath, $storageName . ':' . $relativePath);
        } finally {
            fclose($stream);
        }

        $localPath = $this->storageDisk->localPath($relativePath);
        if ($localPath !== null) {
            $this->asset->file = new File($localPath);
        }
    }

    /**
     * Save the asset to the database
     */
    private function saveAsset(): void
    {
        // Save the asset to the database
        $model = AssetModel::init(false);
        $model->save($this->asset);

        $errors = $model->errors();

        if (! in_array($errors, [null, []], true)) {
            throw AssetException::forDatabaseError($errors);
        }

        $this->asset->id = $model->getInsertID();

        $this->asset->created_at = Time::now();
        $this->asset->updated_at = Time::now();

        // If variants are processed on the queue, we must add queue job for processing
        if (isset($this->assetVariants) && $this->assetVariants->onQueue) {
            AssetVariantsProcess::onQueue(
                $this->asset,
                $this->setupAssetCollection->getCollectionDefinition(),
            );
        }

        unset($this->asset->file); // Unset the file property unless you need it later

        // If the asset was saved, we can now connect it to the entity
        $autoConnectInstance = $this->subjectEntity->assetConnectInstance();
        if ($autoConnectInstance !== null) {
            $autoConnectInstance->addAsset($this->asset);
        }

        // Trigger asset.created event
        Events::trigger(AssetCreated::name(), AssetCreated::createFromAsset($this->asset, $this->subjectEntity));
    }

    /**
     * Process file variants if the collection implements FileVariantInterface
     */
    private function processFileVariants(): void
    {
        $definition = $this->setupAssetCollection->getCollectionDefinition();

        if (! $definition instanceof AssetVariantsInterface) {
            return;
        }

        $this->assetVariants = new AssetVariants(
            $this->pathGenerator,
            $this->asset,
        );

        $definition->variants($this->assetVariants, $this->asset);

        if (! $this->assetVariants->onQueue) {
            // If the definition indicates that variants should be processed immediately,
            AssetVariantsProcess::run($this->asset, $definition);
        }
    }

    /**
     * Clean garbage if failed to store the asset any reason
     */
    private function cleanGarbage(): void
    {
        if ($this->storageDisk !== null && $this->storedPath !== null) {
            try {
                self::removeStoragePath($this->storageDisk->name(), $this->storedPath);
            } catch (Throwable $exception) {
                // Log the error but do not throw it, as we are already handling an exception
                log_message('error', 'Failed to clean up garbage after asset storage failure: {message}', ['message' => $exception->getMessage()]);
            }
        }

        if ($this->asset->id > 0) {
            // If the asset was saved, delete it from the database
            $model = AssetModel::init(false);
            $model->delete($this->asset->id, true);
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
        // Get the maximum number of items allowed in this collection
        $maxItems = $this->collection->getMaximumNumberOfItemsInCollection();

        // If no maximum is set (0) or it's set to 1 (which means only the current asset should exist),
        // then we don't need to do anything
        if ($maxItems <= 0) {
            return;
        }

        // Get the AssetModel
        $model = AssetModel::init(false);
        $ids   = AssetModel::init(false)
            ->where([
                'collection'  => $this->asset->collection,
                'entity_type' => $this->asset->entity_type,
                'entity_id'   => $this->asset->entity_id,
                'deleted_at'  => null, // Only consider non-deleted assets
            ])->orderBy('created_at', 'DESC')
            ->limit(2147483647) // Use a large limit to get all assets in the collection, int max is 2147483647 for best compatibility
            ->offset($maxItems) // Skip the newest $maxItems (which we want to keep)
            ->findColumn('id');

        if (in_array($ids, [null, []], true)) {
            // No assets to delete, return early
            return;
        }

        // Files from storage will be deleted in queue, so we can safely delete them from the database
        $model->whereIn('id', $ids)->delete();

        $autoConnectInstance = $this->subjectEntity->assetConnectInstance();
        if ($autoConnectInstance !== null) {
            foreach ($ids as $id) {
                $autoConnectInstance->removeAssetById((int) $id);
            }
        }
    }
}
