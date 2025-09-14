<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Traits;

use CodeIgniter\Entity\Entity;
use CodeIgniter\Files\File;
use CodeIgniter\HTTP\Files\UploadedFile;
use Config\Services;
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
    final public function addAsset(File|string|UploadedFile $file): AssetAdder
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
     * @param class-string<AssetCollectionDefinitionInterface>|null $collection The collection to get assets from
     *
     * @return list<Asset> An array of assets or a single asset
     */
    final public function getAssets(?string $collection = null): array
    {
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
        return $this->assetConnectInstance->deleteAssetsForEntity($this, $collection);
    }

    /**
     * Add an asset from a request file
     *
     * @param string ...$keyNames The name of the file input field in the request
     *
     * @return AssetAdderMultiple An instance of AssetAdderMultiple to configure and save the asset
     *
     * @throws AssetException|FileException|InvalidArgumentException
     */
    public function addAssetFromRequest(string ...$keyNames): AssetAdderMultiple
    {
        if ($keyNames === []) {
            throw new InvalidArgumentException('At least one key name must be provided.');
        }

        $request = Services::request();

        $uploadedFiles = [];

        foreach ($request->getFiles() as $field => $files) {
            if (! in_array($field, $keyNames, true)) {
                continue;
            }
            $files = is_array($files) ? $files : [$files];

            foreach ($files as $file) {
                if (! $file instanceof UploadedFile || ! $file->isValid()) {
                    throw FileException::forInvalidFile($field);
                }

                if (! isset($uploadedFiles[$field])) {
                    $uploadedFiles[$field] = [];
                }

                $uploadedFiles[$field][] = $file;
            }
        }

        return new AssetAdderMultiple($uploadedFiles, $this);
    }

    /**
     * Add an asset from a base64 encoded string
     *
     * @param string $base64data The base64 encoded data
     *
     * @return AssetAdder An instance of AssetAdder to configure and save the asset
     *
     * @throws AssetException|FileException
     */
    public function addAssetFromBase64(string $base64data): AssetAdder
    {
        // Decode the base64 data
        $data = base64_decode($base64data, true);

        if ($data === false) {
            throw FileException::forInvalidFile('base64data');
        }

        // Create a temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'asset');
        file_put_contents($tempFile, $data);

        // Create a File instance
        $file = new File($tempFile);

        // Add the asset
        $assetAdder = $this->addAsset($file);

        // Set up a callback to delete the temporary file after the asset is stored
        $assetAdder->preservingOriginal();

        return $assetAdder;
    }

    /**
     * Add an asset from a string
     *
     * @param string $string The string data
     *
     * @return AssetAdder An instance of AssetAdder to configure and save the asset
     *
     * @throws AssetException|FileException
     */
    public function addMediaFromString(string $string, string $filename): AssetAdder
    {
        // Create a temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'asset');
        file_put_contents($tempFile, $string);

        // Create a File instance
        $file = new File($tempFile);

        // Add the asset
        $assetAdder = $this->addAsset($file)
            ->usingFileName($filename)
            ->usingName($filename);

        // Set up a callback to delete the temporary file after the asset is stored
        $assetAdder->preservingOriginal(false);

        return $assetAdder;
    }
}
