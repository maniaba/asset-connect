<?php

declare(strict_types=1);

namespace Test\Support;

use CodeIgniter\Entity\Entity;
use Maniaba\AssetConnect\AssetCollection\SetupAssetCollection;
use Maniaba\AssetConnect\Contracts\AssetConnectEntityInterface;
use Maniaba\AssetConnect\Traits\UseAssetConnectTrait;
use Override;

class TestEntity extends Entity implements AssetConnectEntityInterface
{
    use UseAssetConnectTrait;

    public int $id = 123;

    #[Override]
    public function setupAssetConnect($setup): void
    {
        // Mock implementation - the setup collection will handle the actual setup
        if ($setup instanceof SetupAssetCollection) {
            // Set some default values for testing
            $setup->setFileNameSanitizer(static fn (string $fileName): string => $fileName);
            $setup->setSubjectPrimaryKeyAttribute('id');
        }
    }
}
