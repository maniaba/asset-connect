<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Contracts;

use CodeIgniter\Files\File;
use CodeIgniter\HTTP\Files\UploadedFile;
use Maniaba\AssetConnect\Asset\Asset;
use Maniaba\AssetConnect\Asset\AssetAdder;
use Maniaba\AssetConnect\Asset\AssetAdderMultiple;
use Maniaba\AssetConnect\Asset\Interfaces\AssetCollectionDefinitionInterface;
use Maniaba\AssetConnect\AssetCollection\Interfaces\SetupAssetCollectionInterface;
use Maniaba\AssetConnect\AssetConnect;
use Maniaba\AssetConnect\Exceptions\AssetException;
use Maniaba\AssetConnect\Exceptions\FileException;
use Maniaba\AssetConnect\Exceptions\InvalidArgumentException;

/**
 * Interface AssetConnectEntityInterface
 *
 * Defines the contract for entities that can manage asset connections.
 * This interface provides methods to add, retrieve, and delete assets associated with an entity.
 */
interface AssetConnectEntityInterface
{
    /**
     * Initialize the asset connection for this entity
     *
     * This method should be called in the constructor of the entity to set up the asset connection.
     */
    public function loadAssetConnect(AssetConnect $assetConnect): void;

    /**
     * Get the asset connection for this entity
     *
     * @return AssetConnect|null Returns the asset connection if it exists, otherwise null
     */
    public function assetConnectInstance(): ?AssetConnect;

    /**
     * Setup the asset connection for this entity
     *
     * This method should be implemented by the entity to define how assets are connected.
     *
     * @param SetupAssetCollectionInterface $setup The setup object to configure the asset connection
     */
    public function setupAssetConnect(SetupAssetCollectionInterface $setup): void;

    /**
     * Add an asset to the entity
     *
     * @param File|string|UploadedFile $file The file to add as an asset
     *
     * @return AssetAdder An instance of AssetAdder to configure and save the asset
     *
     * @throws AssetException|FileException
     */
    public function addAsset(File|string|UploadedFile $file): AssetAdder;

    /**
     * Get all assets associated with this entity
     *
     * @param class-string<AssetCollectionDefinitionInterface>|null $collection The collection to get assets from
     *
     * @return list<Asset> An array of assets or a single asset
     */
    public function getAssets(?string $collection = null): array;

    /**
     * Get the first asset associated with this entity
     *
     * @param class-string<AssetCollectionDefinitionInterface>|null $collection The collection to get the asset from
     *
     * @return Asset|null The first asset or null if none exists
     */
    public function getFirstAsset(?string $collection = null): ?Asset;

    /**
     * Get the last asset associated with this entity
     *
     * @param class-string<AssetCollectionDefinitionInterface>|null $collection The collection to get the asset from
     *
     * @return Asset|null The last asset or null if none exists
     */
    public function getLastAsset(?string $collection = null): ?Asset;

    /**
     * Delete all assets associated with this entity
     *
     * @param string|null $collection The collection to delete assets from
     *
     * @return bool True if assets were deleted, false otherwise
     */
    public function deleteAssets(?string $collection = null): bool;

    /**
     * Add an asset from a request file
     *
     * @param string ...$keyNames The name of the file input field in the request
     *
     * @return AssetAdderMultiple An instance of AssetAdderMultiple to configure and save the asset
     *
     * @throws AssetException|FileException|InvalidArgumentException
     */
    public function addAssetFromRequest(string ...$keyNames): AssetAdderMultiple;

    /**
     * Add an asset from a base64 encoded string
     *
     * @param string $base64data The base64 encoded data
     *
     * @return AssetAdder An instance of AssetAdder to configure and save the asset
     *
     * @throws AssetException|FileException
     */
    public function addAssetFromBase64(string $base64data): AssetAdder;

    /**
     * Add an asset from a string
     *
     * @param string $string   The string data
     * @param string $filename The filename to use
     *
     * @return AssetAdder An instance of AssetAdder to configure and save the asset
     *
     * @throws AssetException|FileException
     */
    public function addMediaFromString(string $string, string $filename): AssetAdder;
}
