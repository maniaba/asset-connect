<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Traits;

use CodeIgniter\Files\File;
use Maniaba\FileConnect\Asset\Asset;
use Maniaba\FileConnect\Asset\AssetAdder;
use Maniaba\FileConnect\AssetConnect;
use Maniaba\FileConnect\Exceptions\AssetException;
use Maniaba\FileConnect\Exceptions\FileException;
use Maniaba\FileConnect\Interfaces\AssetCollection\SetupAssetCollection;

trait UseAssetConnectTrait
{
    /**
     * Setup the asset connection for this entity
     *
     * This method should be implemented by the entity to define how assets are connected.
     *
     * @param SetupAssetCollection $setup The setup object to configure the asset connection
     */
    abstract public function setupAssetConnect(SetupAssetCollection $setup): void;

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
}
