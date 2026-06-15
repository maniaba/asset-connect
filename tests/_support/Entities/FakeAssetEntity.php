<?php

declare(strict_types=1);

namespace Tests\Support\Entities;

use CodeIgniter\Entity\Entity;
use Maniaba\AssetConnect\AssetCollection\Interfaces\SetupAssetCollectionInterface;
use Maniaba\AssetConnect\Contracts\AssetConnectEntityInterface;
use Maniaba\AssetConnect\Traits\UseAssetConnectTrait;
use Override;
use Tests\Support\AssetCollections\FakeDocumentCollection;

final class FakeAssetEntity extends Entity implements AssetConnectEntityInterface
{
    use UseAssetConnectTrait;

    protected $casts = [
        'id' => 'int',
    ];

    #[Override]
    public function setupAssetConnect(SetupAssetCollectionInterface $setup): void
    {
        $setup
            ->setDefaultCollectionDefinition(FakeDocumentCollection::class)
            ->setSubjectPrimaryKeyAttribute('id')
            ->setFileNameSanitizer(static fn (string $fileName): string => str_replace(['#', '/', '\\', ' '], '-', $fileName));
    }
}
