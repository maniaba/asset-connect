<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Asset;

use CodeIgniter\Entity\Entity;
use CodeIgniter\Events\Events;
use CodeIgniter\Files\File;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\I18n\Time;
use Maniaba\AssetConnect\Asset\Interfaces\AssetCollectionDefinitionInterface;
use Maniaba\AssetConnect\AssetCollection\AssetCollection;
use Maniaba\AssetConnect\AssetCollection\SetupAssetCollection;
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
use Maniaba\AssetConnect\Traits\UseAssetConnectTrait;
use Throwable;

final class AssetPersistenceManager
{
    private readonly PathGenerator $pathGenerator;
    private string $storePath;
    private readonly AssetCollection $collection;
    private AssetVariants $assetVariants;

    public function __construct(
        /** @var Entity&UseAssetConnectTrait $subjectEntity The entity to which the asset is being added */
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
     * Store the asset in the appropriate storage path based on the collection type
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
        $file            = $this->asset->file;
        $sourcePath      = $file->getRealPath();
        $this->storePath = $storePath = $this->pathGenerator->getPath();

        // Set the asset path and file properties
        $this->asset->path = $storePath . $this->asset->file_name;
        // Set the asset metadata basic info properties
        $this->asset->metadata->basicInfo->setStorageBaseDirectoryPath($this->pathGenerator->getStoreDirectory());
        $this->asset->metadata->basicInfo->setFileRelativePath($this->pathGenerator->getFileRelativePath());

        $this->asset->file = new File($this->asset->path);

        if (! file_exists($sourcePath)) {
            throw FileException::forFileNotFound($sourcePath);
        }

        // Preserve the original file, delete the temporary file
        if ($file instanceof UploadedFile) {
            $result = $file->move($storePath, $this->asset->file_name);
            if (! $result) {
                throw FileException::forCannotMoveFile($sourcePath, $storePath);
            }
        } elseif ($file instanceof File) {
            // For CodeIgniter File objects, we can use the move method
            if (! copy($sourcePath, $this->asset->path)) {
                throw FileException::forCannotCopyFile($sourcePath, $this->asset->path);
            }
        } else {
            throw new InvalidArgumentException('Unsupported asset type for storage.');
        }
    }

    /**
     * Save the asset to the database
     */
    private function saveAsset(): void
    {
        // Save the asset to the database
        $model = model(AssetModel::class, false);
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
        if ($this->setupAssetCollection->getCollectionDefinition() instanceof AssetVariantsInterface) {
            /** @var AssetCollectionDefinitionInterface&AssetVariantsInterface $definition */
            $definition          = $this->setupAssetCollection->getCollectionDefinition();
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
    }

    /**
     * Clean garbage if failed to store the asset any reason
     */
    private function cleanGarbage(): void
    {
        if (isset($this->storePath)) {
            // remove directory if it exists recursively
            try {
                self::removeStoragePath($this->storePath);
            } catch (Throwable $exception) {
                // Log the error but do not throw it, as we are already handling an exception
                log_message('error', 'Failed to clean up garbage after asset storage failure: {message}', ['message' => $exception->getMessage()]);
            }
        }

        if ($this->asset->id > 0) {
            // If the asset was saved, delete it from the database
            $model = model(AssetModel::class, false);
            $model->delete($this->asset->id, true);
        }
    }

    public static function removeStoragePath(string $path): void
    {
        helper('filesystem');
        if (is_dir($path)) {
            try {
                delete_files($path, true, false, true);
                @rmdir($path);
            } catch (Throwable $exception) {
                log_message('error', 'Failed to remove storage path: {message}', ['message' => $exception->getMessage()]);
            }
        } elseif (file_exists($path)) {
            @unlink($path);
        }
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
        $model = model(AssetModel::class, false);
        $ids   = model(AssetModel::class, false)
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
