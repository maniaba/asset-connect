<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Traits;

use CodeIgniter\Entity\Entity;
use CodeIgniter\Files\File;
use Maniaba\FileConnect\Asset\Asset;
use Maniaba\FileConnect\Asset\AssetAdder;
use Maniaba\FileConnect\Asset\Interfaces\AssetCollectionDefinitionInterface;
use Maniaba\FileConnect\AssetCollection\Interfaces\SetupAssetCollectionInterface;
use Maniaba\FileConnect\AssetConnect;
use Maniaba\FileConnect\Exceptions\AssetException;
use Maniaba\FileConnect\Exceptions\FileException;

/**
 * Trait UseAssetConnectTrait
 *
 * This trait provides methods to manage asset connections for entities.
 * It allows adding, retrieving, and deleting assets associated with the entity.
 *
 * @property-read list<Asset> $assets
 */
trait UseAssetConnectTrait
{
    private AssetConnect $assetConnectInstance;

    /**
     * Initialize the asset connection for this entity
     *
     * This method should be called in the constructor of the entity to set up the asset connection.
     */
    public function loadAssetConnect(AssetConnect $assetConnect): void
    {
        $this->assetConnectInstance = $assetConnect;
    }

    /**
     * Get the asset connection for this entity
     *
     * @return AssetConnect|null Returns the asset connection if it exists, otherwise null
     */
    public function assetConnectInstance(): ?AssetConnect
    {
        return $this->assetConnectInstance ?? null;
    }

    /**
     * Setup the asset connection for this entity
     *
     * This method should be implemented by the entity to define how assets are connected.
     *
     * @param SetupAssetCollectionInterface $setup The setup object to configure the asset connection
     */
    abstract public function setupAssetConnect(SetupAssetCollectionInterface $setup): void;

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

        /** @var Entity&UseAssetConnectTrait $this */
        return new AssetAdder($this, $file);
    }

    /**
     * Get all assets associated with this entity
     *
     * @param class-string<AssetCollectionDefinitionInterface>|null $collection The collection to get assets from
     *
     * @return list<Asset> An array of assets or a single asset
     */
    final public function getAssets(?string $collection = null): array
    {
        /** @var Entity&UseAssetConnectTrait $this */
        return $this->assetConnectInstance->getAssetsForEntity($this, $collection);
    }

    /**
     * Get the first asset associated with this entity
     *
     * @param class-string<AssetCollectionDefinitionInterface>|null $collection The collection to get the asset from
     *
     * @return Asset|null The first asset or null if none exists
     */
    final public function getFirstAsset(?string $collection = null): ?Asset
    {
        $assets = $this->getAssets($collection);

        if ($assets === []) {
            return null;
        }

        return reset($assets) ?: null;
    }

    /**
     * Get the last asset associated with this entity
     *
     * @param class-string<AssetCollectionDefinitionInterface>|null $collection The collection to get the asset from
     *
     * @return Asset|null The last asset or null if none exists
     */
    final public function getLastAsset(?string $collection = null): ?Asset
    {
        $assets = $this->getAssets($collection);

        if ($assets === []) {
            return null;
        }

        return end($assets) ?: null;
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
        /** @var Entity&UseAssetConnectTrait $this */
        return $this->assetConnectInstance->deleteAssetsForEntity($this, $collection);
    }
}
