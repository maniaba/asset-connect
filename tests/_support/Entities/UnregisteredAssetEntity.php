<?php

declare(strict_types=1);

namespace Tests\Support\Entities;

use CodeIgniter\Entity\Entity;
use Maniaba\AssetConnect\AssetCollection\Interfaces\SetupAssetCollectionInterface;
use Maniaba\AssetConnect\Contracts\AssetConnectEntityInterface;
use Maniaba\AssetConnect\Traits\UseAssetConnectTrait;
use Override;

final class UnregisteredAssetEntity extends Entity implements AssetConnectEntityInterface
{
    use UseAssetConnectTrait;

    #[Override]
    public function setupAssetConnect(SetupAssetCollectionInterface $setup): void
    {
        unset($setup);
    }
}
