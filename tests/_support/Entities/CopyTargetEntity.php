<?php

declare(strict_types=1);

namespace Tests\Support\Entities;

use CodeIgniter\Entity\Entity;
use Maniaba\AssetConnect\AssetCollection\Interfaces\SetupAssetCollectionInterface;
use Maniaba\AssetConnect\Contracts\AssetConnectEntityInterface;
use Maniaba\AssetConnect\Traits\UseAssetConnectTrait;
use Override;

final class CopyTargetEntity extends Entity implements AssetConnectEntityInterface
{
    use UseAssetConnectTrait;

    #[Override]
    public function setupAssetConnect(SetupAssetCollectionInterface $setup): void
    {
        $setup->setSubjectPrimaryKeyAttribute('target_id');
    }
}
