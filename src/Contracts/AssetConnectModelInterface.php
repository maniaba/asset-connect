<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Contracts;

use Closure;
use Maniaba\AssetConnect\Models\AssetModel;

/**
 * Interface AssetConnectModelInterface
 *
 * Defines the contract for models that can filter and manage asset connections.
 * This interface provides methods to filter assets and access the asset model.
 */
interface AssetConnectModelInterface
{
    /**
     * Filter assets associated with this entity using a closure.
     *
     * @param Closure(AssetModel $model): void $filter A closure that takes an Asset and returns a boolean indicating whether to keep the asset.
     */
    public function filterAssets(Closure $filter): static;

    /**
     * Get the asset connect model instance.
     *
     * @return AssetModel The asset model instance
     */
    public function assetConnectModel(): AssetModel;
}
