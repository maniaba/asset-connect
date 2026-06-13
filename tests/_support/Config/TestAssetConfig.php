<?php

declare(strict_types=1);

namespace Tests\Support\Config;

use CodeIgniter\Entity\Entity;
use Maniaba\AssetConnect\AssetCollection\DefaultAssetCollection;
use Maniaba\AssetConnect\Config\Asset as BaseAsset;
use Tests\Support\AssetCollections\FakeAvatarCollection;
use Tests\Support\AssetCollections\FakeDocumentCollection;
use Tests\Support\Entities\FakeAssetEntity;
use Tests\Support\TestAssetCollection;
use Tests\Support\TestEntity;

/**
 * Test configuration for Asset Connect
 * This extends the base configuration and adds test-specific entity and collection definitions
 */
final class TestAssetConfig extends BaseAsset
{
    /**
     * {@inheritDoc}
     */
    public array $entityKeyDefinitions = [
        TestEntity::class      => 'test_entity',
        FakeAssetEntity::class => 'fake_asset_entity',
        Entity::class          => 'basic_entity',
    ];

    /**
     * {@inheritDoc}
     */
    public array $collectionKeyDefinitions = [
        TestAssetCollection::class    => 'test_collection',
        FakeDocumentCollection::class => 'fake_documents',
        FakeAvatarCollection::class   => 'fake_avatars',
        DefaultAssetCollection::class => 'default_collection',
    ];

    /**
     * {@inheritDoc}
     */
    public array $storages = [
        'public' => [
            'driver'     => 'local',
            'root'       => HOMEPATH . 'build/asset-connect/public',
            'public_url' => 'assets/storage',
            'visibility' => 'public',
        ],
        'protected' => [
            'driver'     => 'local',
            'root'       => HOMEPATH . 'build/asset-connect/protected',
            'public_url' => 'assets/protected',
            'visibility' => 'protected',
        ],
    ];
}
