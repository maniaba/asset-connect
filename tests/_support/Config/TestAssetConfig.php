<?php

declare(strict_types=1);

namespace Tests\Support\Config;

use CodeIgniter\Entity\Entity;
use Maniaba\AssetConnect\AssetCollection\DefaultAssetCollection;
use Maniaba\AssetConnect\Config\Asset as BaseAsset;
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
        TestEntity::class => 'test_entity',
        Entity::class     => 'basic_entity',
    ];

    /**
     * {@inheritDoc}
     */
    public array $collectionKeyDefinitions = [
        DefaultAssetCollection::class => 'default_collection',
    ];
}
