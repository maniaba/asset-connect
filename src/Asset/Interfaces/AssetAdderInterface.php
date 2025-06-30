<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Asset\Interfaces;

use CodeIgniter\Files\File;
use Maniaba\FileConnect\Asset\Asset;

/**
 * Interface for asset adders.
 *
 * This interface defines the methods that should be implemented by classes
 * that add assets to collections.
 */
interface AssetAdderInterface
{
    /**
     * Sets the name of the asset.
     *
     * @param string $name The name to set for the asset.
     */
    public function usingName(string $name): self;

    /**
     * Sets the file name of the asset.
     *
     * @param string $fileName The file name to set for the asset.
     */
    public function usingFileName(string $fileName): self;

    /**
     * Sets whether to preserve the original file.
     *
     * @param bool $preserveOriginal Whether to preserve the original file.
     */
    public function preservingOriginal(bool $preserveOriginal = true): self;

    /**
     * Sets the order of the asset.
     *
     * @param int $order The order to set for the asset.
     */
    public function setOrder(int $order): self;

    /**
     * Adds custom properties to the asset.
     *
     * @param array<string, mixed> $customProperties An associative array of custom properties.
     */
    public function withCustomProperties(array $customProperties): self;

    /**
     * Store the asset in the specified collection
     *
     * @param AssetCollectionDefinitionInterface|string|null $collection The collection to store the asset in
     *
     * @return Asset The stored asset
     */
    public function toAssetCollection(AssetCollectionDefinitionInterface|string|null $collection = null): Asset;
}
