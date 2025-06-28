<?php

declare(strict_types=1);

namespace Maniaba\FileConnect;

use CodeIgniter\Files\File;
use Maniaba\FileConnect\Asset\Asset;
use Maniaba\FileConnect\Entities\PendingAsset;
use Maniaba\FileConnect\Enums\AssetCollectionType;
use Maniaba\FileConnect\Enums\AssetDiskType;
use Maniaba\FileConnect\Models\AssetModel;
use RuntimeException;

class AssetConnect
{
    protected AssetModel $assetModel;

    protected array $config;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->assetModel = new AssetModel();
        $this->config     = config('Asset')->tables;
    }

    /**
     * Add an asset to an entity
     *
     * @param object      $entity           The entity to add the asset to
     * @param File|string $file             The file to add as an asset
     * @param array       $customProperties Custom properties to store with the asset
     * @param string|null $collection       The collection to add the asset to
     * @param string      $diskName         The disk name
     * @param array       $manipulations    Manipulations to apply to the asset
     * @param array       $customHeaders    Custom headers
     *
     * @return Asset The created asset
     *
     * @throws RuntimeException If the entity doesn't have an ID, the file is not accepted for the collection, or the collection size limit is reached
     */
    public function addAssetToEntity(
        object $entity,
        File|string $file,
        array $customProperties = [],
        ?string $collection = null,
        string $diskName = '',
        array $manipulations = [],
        array $customHeaders = [],
    ): Asset {
        // Get the entity class name and ID
        $entityClass = $entity::class;
        $entityId    = $entity->id ?? null;

        if ($entityId === null) {
            throw new RuntimeException('Entity must have an ID to add assets');
        }

        // Process the file
        $fileInfo = $this->processFile($file);

        // Check if the file is accepted for the collection
        $collectionName   = $collection ?? AssetCollectionType::DEFAULT;
        $collectionConfig = null;

        if (method_exists($entity, 'getAssetCollection')) {
            $collectionConfig = $entity->getAssetCollection($collectionName);
        }

        if ($collectionConfig !== null) {
            // Create a pending asset from the file info
            $pendingAsset = PendingAsset::createFromFileInfo($fileInfo);

            // Check if the file is accepted for the collection
            if (! ($collectionConfig->acceptsFile)($pendingAsset, $entity)) {
                throw new RuntimeException("File is not accepted for collection '{$collectionName}'");
            }

            // Check if the file's MIME type is accepted for the collection
            if (! empty($collectionConfig->acceptsMimeTypes) && ! in_array($pendingAsset->getMimeType(), $collectionConfig->acceptsMimeTypes, true)) {
                throw new RuntimeException("MIME type '{$pendingAsset->getMimeType()}' is not accepted for collection '{$collectionName}'");
            }

            // Use the disk name from the collection config if not provided
            if ($diskName === '' && $collectionConfig->diskName !== '') {
                $diskName = $collectionConfig->diskName;
            }
        }

        // Create the asset record
        $assetData = [
            'entity_type'       => $entityClass,
            'entity_id'         => $entityId,
            'collection'        => $collectionName,
            'file_name'         => $fileInfo['name'],
            'mime_type'         => $fileInfo['mime_type'],
            'size'              => $fileInfo['size'],
            'disk'              => $diskName ?: AssetDiskType::LOCAL->value, // Use provided disk name or default to local disk
            'path'              => $fileInfo['path'],
            'custom_properties' => json_encode($customProperties),
            'created_at'        => date('Y-m-d H:i:s'),
            'updated_at'        => date('Y-m-d H:i:s'),
        ];

        // Add manipulations if provided
        if (! empty($manipulations)) {
            $assetData['manipulations'] = json_encode($manipulations);
        }

        // Add custom headers if provided
        if (! empty($customHeaders)) {
            $assetData['custom_headers'] = json_encode($customHeaders);
        }

        $assetId = $this->assetModel->insert($assetData);
        $asset   = $this->getAssetById($assetId);

        // Enforce collection size limit if configured
        if ($collectionConfig !== null && $collectionConfig->collectionSizeLimit !== false) {
            $this->enforceCollectionSizeLimit($entity, $collectionName, $collectionConfig->collectionSizeLimit);
        }

        return $asset;
    }

    /**
     * Get assets for an entity
     *
     * @param object      $entity     The entity to get assets for
     * @param string|null $collection The collection to get assets from
     *
     * @return array|Asset An array of assets or a single asset
     */
    public function getAssetsForEntity(object $entity, ?string $collection = null): array|Asset
    {
        // Get the entity class name and ID
        $entityClass = $entity::class;
        $entityId    = $entity->id ?? null;

        if ($entityId === null) {
            return [];
        }

        // Build the query
        $query = $this->assetModel->where('entity_type', $entityClass)
            ->where('entity_id', $entityId);

        // Filter by collection if provided
        if ($collection !== null) {
            $query->where('collection', $collection);
        }

        // Get the assets
        $assets = $query->findAll();

        // Convert to Asset entities
        $assetEntities = [];

        foreach ($assets as $asset) {
            $assetEntities[] = new Asset($asset);
        }

        // Return a single asset if there's only one
        if (count($assetEntities) === 1) {
            return $assetEntities[0];
        }

        return $assetEntities;
    }

    /**
     * Delete assets for an entity
     *
     * @param object      $entity     The entity to delete assets for
     * @param string|null $collection The collection to delete assets from
     *
     * @return bool True if assets were deleted, false otherwise
     */
    public function deleteAssetsForEntity(object $entity, ?string $collection = null): bool
    {
        // Get the entity class name and ID
        $entityClass = $entity::class;
        $entityId    = $entity->id ?? null;

        if ($entityId === null) {
            return false;
        }

        // Build the query
        $query = $this->assetModel->where('entity_type', $entityClass)
            ->where('entity_id', $entityId);

        // Filter by collection if provided
        if ($collection !== null) {
            $query->where('collection', $collection);
        }

        // Get the assets to delete their files
        $assets = $query->findAll();

        // Delete the files
        foreach ($assets as $asset) {
            $this->deleteFile($asset->path);
        }

        // Delete the asset records
        return $query->delete() !== false;
    }

    /**
     * Get an asset by ID
     *
     * @param int $id The asset ID
     *
     * @return Asset The asset
     */
    public function getAssetById(int $id): Asset
    {
        $asset = $this->assetModel->find($id);

        if ($asset === null) {
            throw new RuntimeException('Asset not found');
        }

        return new Asset($asset);
    }

    /**
     * Process a file and return its information
     *
     * @param File|string $file The file to process
     *
     * @return array The file information
     */
    protected function processFile(File|string $file): array
    {
        // Convert string path to File object if needed
        if (is_string($file)) {
            $file = new File($file);
        }

        // Generate a unique filename
        $fileName     = md5(uniqid() . $file->getRandomName());
        $extension    = $file->getExtension();
        $fullFileName = $fileName . '.' . $extension;

        // Create the storage directory if it doesn't exist
        $storagePath = WRITEPATH . 'uploads/assets';
        if (! is_dir($storagePath)) {
            mkdir($storagePath, 0755, true);
        }

        // Move the file to the storage directory
        $file->move($storagePath, $fullFileName);

        // Return the file information
        return [
            'name'      => $fullFileName,
            'mime_type' => $file->getMimeType(),
            'size'      => $file->getSize(),
            'path'      => 'uploads/assets/' . $fullFileName,
        ];
    }

    /**
     * Delete a file
     *
     * @param string $path The file path
     *
     * @return bool True if the file was deleted, false otherwise
     */
    protected function deleteFile(string $path): bool
    {
        $fullPath = WRITEPATH . $path;

        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }

        return false;
    }

    /**
     * Enforce collection size limit
     *
     * @param object $entity         The entity
     * @param string $collectionName The collection name
     * @param int    $limit          The collection size limit
     */
    protected function enforceCollectionSizeLimit(object $entity, string $collectionName, int $limit): void
    {
        // Get all assets in the collection
        $assets = $this->getAssetsForEntity($entity, $collectionName);

        // If the collection has more assets than the limit, delete the oldest ones
        if (is_array($assets) && count($assets) > $limit) {
            // Sort assets by created_at date (oldest first)
            usort($assets, static fn (Asset $a, Asset $b) => strtotime($a->created_at) - strtotime($b->created_at));

            // Delete the oldest assets until the collection size is within the limit
            $assetsToDelete = array_slice($assets, 0, count($assets) - $limit);

            foreach ($assetsToDelete as $asset) {
                $this->deleteFile($asset->getRelativePath());
                $this->assetModel->delete($asset->id);
            }
        }
    }
}
