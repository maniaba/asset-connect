<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Repositories;

use Maniaba\AssetConnect\Asset\Asset;
use Maniaba\AssetConnect\Models\AssetModel;
use Maniaba\AssetConnect\Repositories\Interfaces\AssetRepositoryInterface;
use Override;

final class AssetRepository implements AssetRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    #[Override]
    public function find(int $id): ?Asset
    {
        $assetModel = model(AssetModel::class, false);

        return $assetModel->find($id);
    }
}
