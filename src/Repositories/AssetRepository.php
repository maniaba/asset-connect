<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Repositories;

use Maniaba\FileConnect\Asset\Asset;
use Maniaba\FileConnect\Models\AssetModel;
use Maniaba\FileConnect\Repositories\Interfaces\AssetRepositoryInterface;
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
