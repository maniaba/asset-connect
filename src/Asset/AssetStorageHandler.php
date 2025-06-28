<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Asset;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Files\File;
use CodeIgniter\HTTP\Files\UploadedFile;
use Maniaba\FileConnect\AssetCollection\AssetCollection;
use Maniaba\FileConnect\AssetCollection\FileVariants;
use Maniaba\FileConnect\AssetCollection\SetupAssetCollection;
use Maniaba\FileConnect\Enums\AssetVisibility;
use Maniaba\FileConnect\Exceptions\AssetException;
use Maniaba\FileConnect\Exceptions\FileException;
use Maniaba\FileConnect\Exceptions\InvalidArgumentException;
use Maniaba\FileConnect\Interfaces\Asset\AssetCollectionInterface;
use Maniaba\FileConnect\Interfaces\Asset\AuthorizableAssetCollectionInterface;
use Maniaba\FileConnect\Interfaces\Asset\FileVariantInterface;
use Maniaba\FileConnect\Models\AssetModel;
use Maniaba\FileConnect\PathGenerator\PathGenerator;
use Maniaba\FileConnect\PathGenerator\PathGeneratorFactory;
use Throwable;

final class AssetStorageHandler
{
    private readonly AssetCollection $collection;
    private readonly PathGenerator $pathGenerator;
    private string $storePath;

    public function __construct(
        private Asset $asset,
        private AssetCollectionInterface|string $collectionDefinition,
        private readonly SetupAssetCollection $setupAssetCollection,
    ) {
        if (! is_subclass_of($collectionDefinition, AssetCollectionInterface::class)) {
            throw new InvalidArgumentException(sprintf(
                'Expected a class implementing %s, got %s',
                AssetCollectionInterface::class,
                $collectionDefinition,
            ));
        }

        if (is_string($collectionDefinition)) {
            $this->collectionDefinition = new $collectionDefinition();
        }

        // Create a new AssetCollection to store the definition
        $this->collection = new AssetCollection($this->setupAssetCollection);

        // Call the definition method on the collection to set up validation rules
        $this->collectionDefinition->definition($this->collection);
        // Set the collection name
        $this->asset->collection = $this->collectionDefinition;
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

            // Determine the storage path based on the collection type
            $this->initializePathGenerator();

            // Store the file
            $this->storeFile();

            // Save the asset to the database
            $this->saveAsset();

            // Process file variants if the collection implements FileVariantInterface
            $this->processFileVariants();

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
     * Determine the storage path based on the collection type
     */
    private function initializePathGenerator(): void
    {
        // If the collection implements AuthorizableAssetCollectionInterface, use the private path
        if ($this->collectionDefinition instanceof AuthorizableAssetCollectionInterface) {
            $this->collection->setVisibility(AssetVisibility::PROTECTED);
        }

        // Ensure the collection has a valid visibility before passing it to the path generator
        $this->pathGenerator = PathGeneratorFactory::create($this->collection);
    }

    /**
     * Store the file
     *
     * @param string $fullPath The full path to store the file
     *
     * @throws FileException
     */
    private function storeFile(): void
    {
        $file            = $this->asset->file;
        $sourcePath      = $file->getRealPath();
        $this->storePath = $storePath = $this->pathGenerator->getPath();

        $this->asset->path = $storePath . $this->asset->file_name;
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

        $this->asset->id = $model->insertID();
    }

    /**
     * Process file variants if the collection implements FileVariantInterface
     */
    private function processFileVariants(): void
    {
        if ($this->collectionDefinition instanceof FileVariantInterface) {
            $variants = new FileVariants();
            // $this->collectionDefinition->variants($variants, $this->asset);
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
        $model    = model(AssetModel::class, false);
        $idsQuery = model(AssetModel::class, false)
            ->select('id')
            ->where([
                'collection'  => $this->asset->collection,
                'entity_type' => $this->asset->entity_type,
                'entity_id'   => $this->asset->entity_id,
                'deleted_at'  => null, // Only consider non-deleted assets
            ])->orderBy('created_at', 'DESC')
            ->limit(2147483647) // Use a large limit to get all assets in the collection, int max is 2147483647 for best compatibility
            ->offset($maxItems) // Skip the newest $maxItems (which we want to keep)
            ->builder();

        // Files from storage will be deleted in queue, so we can safely delete them from the database
        $model->whereIn('id', static fn (BaseBuilder $builder) => $builder->fromSubquery($idsQuery, 'subquery'))->delete();
    }
}
