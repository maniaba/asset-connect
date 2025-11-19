<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Asset\Interfaces;

use Maniaba\AssetConnect\Asset\Asset;
use Maniaba\AssetConnect\Asset\AssetMetadata;

/**
 * Interface for asset adders.
 *
 * This interface defines the methods that should be implemented by classes
 * that add assets to collections.
 */
interface AssetAdderInterface extends AssetDefinitionInterface
{
    /**
     * Store the asset in the specified collection
     *
     * @param AssetCollectionDefinitionInterface|string|null $collection The collection to store the asset in
     *
     * @return Asset The stored asset
     */
    public function toAssetCollection(AssetCollectionDefinitionInterface|string|null $collection = null): Asset;

    /**
     * Get the metadata of the asset being added.
     *
     * @return AssetMetadata The metadata of the asset.
     */
    public function metadata(): AssetMetadata;
}
