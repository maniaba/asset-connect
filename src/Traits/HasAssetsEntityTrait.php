<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Traits;

use CodeIgniter\Files\File;
use Maniaba\FileConnect\Asset\Asset;
use Maniaba\FileConnect\Asset\AssetAdder;
use Maniaba\FileConnect\AssetCollection\AssetCollection;
use Maniaba\FileConnect\AssetConnect;
use Maniaba\FileConnect\Entities\AssetCollectionConfig;
use Maniaba\FileConnect\Exceptions\AssetException;
use Maniaba\FileConnect\Exceptions\FileException;
use Maniaba\FileConnect\Interfaces\Asset\AssetCollectionInterface;

trait HasAssetsEntityTrait
{
    /**
     * The asset collections registered for this entity
     *
     * @var array<string, AssetCollectionConfig>
     */
    public array $assetCollections = [];

    /**
     * Get the definition of the asset collection for this entity
     *
     * @return AssetCollectionInterface|string|null The asset collection definition or null if you want to use the default collection from AssetConfig
     */
    abstract public function registerAssetCollection(): AssetCollectionInterface|string|null;

    /**
     * Add an asset to the entity
     *
     * @param File|string $file The file to add as an asset
     *
     * @return AssetAdder An instance of AssetAdder to configure and save the asset
     *
     * @throws AssetException|FileException
     */
    final public function addAsset(File|string $file): AssetAdder
    {
        if (is_string($file)) {
            $file = new File($file);
        }

        // check if the file exists
        if (! $file->isFile()) {
            throw FileException::forInvalidFile($file->getRealPath());
        }

        return new AssetAdder($this, $file);
    }

    /**
     * Get all assets associated with this entity
     *
     * @param string|null $collection The collection to get assets from
     *
     * @return array|Asset An array of assets or a single asset
     */
    final public function getAssets(?string $collection = null): array|Asset
    {
        $assetConnect = new AssetConnect();

        return $assetConnect->getAssetsForEntity($this, $collection);
    }

    /**
     * Get the first asset associated with this entity
     *
     * @param string|null $collection The collection to get the asset from
     *
     * @return Asset|null The first asset or null if none exists
     */
    final public function getFirstAsset(?string $collection = null): ?Asset
    {
        $assets = $this->getAssets($collection);

        if (is_array($assets) && count($assets) > 0) {
            return $assets[0];
        }

        return $assets instanceof Asset ? $assets : null;
    }

    /**
     * Delete all assets associated with this entity
     *
     * @param string|null $collection The collection to delete assets from
     *
     * @return bool True if assets were deleted, false otherwise
     */
    final public function deleteAssets(?string $collection = null): bool
    {
        $assetConnect = new AssetConnect();

        return $assetConnect->deleteAssetsForEntity($this, $collection);
    }

    /**
     * Get an asset collection for this entity
     *
     * @param string $collection The collection name
     *
     * @return AssetCollection The asset collection
     */
    final public function collection(string $collection = 'default'): AssetCollection
    {
        return new AssetCollection($this, $collection);
    }

    /**
     * Get a registered asset collection
     *
     * @param string $collectionName The collection name
     *
     * @return AssetCollectionConfig|null The asset collection config or null if not found
     */
    public function getAssetCollection(string $collectionName): ?AssetCollectionConfig
    {
        $this->registerAssetCollections();

        return $this->assetCollections[$collectionName] ?? null;
    }

    /**
     * Add an asset collection to the entity
     *
     * @param AssetCollectionConfig $collection The asset collection config
     */
    public function addAssetCollection(AssetCollectionConfig $collection): void
    {
        $this->assetCollections[$collection->name] = $collection;
    }
}
