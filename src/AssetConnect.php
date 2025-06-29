<?php

declare(strict_types=1);

namespace Maniaba\FileConnect;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Entity\Entity;
use CodeIgniter\Files\File;
use Maniaba\FileConnect\Asset\Asset;
use Maniaba\FileConnect\Models\AssetModel;
use Maniaba\FileConnect\Traits\UseAssetConnectTrait;
use RuntimeException;

class AssetConnect
{
    protected AssetModel $assetModel;

    /**
     * @var Config\Asset The configuration for the AssetConnect
     */
    protected BaseConfig $config;

    protected array $relationsInfo = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->assetModel = model(AssetModel::class, false);
        $this->config     = config('Asset');
    }

    public function triggerModelAfterFind(array $data): array
    {
        $this->propertyMappingHelper($data);

        return [];
    }

    public function setRelationsInfo()
    {
    }

    private function propertyMappingHelper(array $data): array
    {
        /** @var list<Entity&UseAssetConnectTrait> $rows */
        $rows = $data['singleton'] ? [$data['data']] : $data['data'];

        foreach ($rows as $row) {
            if (! in_array(UseAssetConnectTrait::class, class_uses($row), true)) {
                throw new RuntimeException('The entity(Model::$returnType) must use the UseAssetConnectTrait trait.');
            }
        }

        dd($rows);

        if ($parentIds === []) {
            unset($this->relationsInfo[$relationKey]);

            return $data;
        }

        $childs = $model->whereIn($model->getModelTable() . '.' . $localKey, array_unique($parentIds))->findAll();

        if ($childs === []) {
            unset($this->relationsInfo[$relationKey]);

            return $data;
        }

        // Step 1: Index children by foreign key
        $childMap = [];

        foreach ($childs as $child) {
            $childMap[$child->{$localKey}][] = $child;
        }
        unset($childs);

        // Step 2: Assign children to parents
        foreach (($data['singleton'] ? [$data['data']] : $data['data']) as &$parent) {
            $parentKeyVal = $parent->{$foreignKey};

            if (isset($childMap[$parentKeyVal])) {
                $value = $oneToMany ? $childMap[$parentKeyVal] : $childMap[$parentKeyVal][0];
                // if can be cloned, clone it
                if ($cacheLevel === CacheLevel::NONE && $value instanceof Entity) {
                    $value = clone $value;
                }
                $parent->{$attribute} = $value;
            }
        }

        unset($childMap, $this->relationsInfo[$relationKey]);

        return $data;
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
