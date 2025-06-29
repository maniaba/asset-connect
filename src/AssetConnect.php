<?php

declare(strict_types=1);

namespace Maniaba\FileConnect;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Entity\Entity;
use Maniaba\FileConnect\Asset\Asset;
use Maniaba\FileConnect\AssetCollection\AssetCollectionDefinitionFactory;
use Maniaba\FileConnect\AssetCollection\SetupAssetCollection;
use Maniaba\FileConnect\Exceptions\InvalidArgumentException;
use Maniaba\FileConnect\Interfaces\Asset\AssetCollectionDefinitionInterface;
use Maniaba\FileConnect\Models\AssetModel;
use Maniaba\FileConnect\Traits\UseAssetConnectTrait;
use RuntimeException;

final class AssetConnect
{
    public readonly AssetModel $assetModel;

    /**
     * @var Config\Asset The configuration for the AssetConnect
     */
    private readonly BaseConfig $config;

    private readonly SetupAssetCollection $setupAssetCollection;
    private array $relationsInfo = [
        'primaryKeys' => [],
        'entityType'  => null,
        'collection'  => null,
    ];

    /**
     * @var array<int, list<Asset>> Cached assets for each collection per entity type.
     */
    private array $assets = [];

    private bool $fetchedForAllCollections = false;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->assetModel           = model(AssetModel::class, false);
        $this->config               = config('Asset');
        $this->setupAssetCollection = new SetupAssetCollection();
    }

    public function triggerModelAfterFind(array $data): array
    {
        /** @var list<Entity&UseAssetConnectTrait> $rows */
        $rows = $data['singleton'] ? [$data['data']] : $data['data'];

        if ($rows === []) {
            return $data;
        }

        $setupDone = false;

        foreach ($rows as $row) {
            if (! in_array(UseAssetConnectTrait::class, class_uses($row), true)) {
                throw new RuntimeException('The entity(Model::$returnType) must use the UseAssetConnectTrait trait.');
            }
            // Make sure the entity sets up the asset connection runs once per entity type
            if (! $setupDone) {
                $row->setupAssetConnect($this->setupAssetCollection);
                $setupDone = true;
            }

            $primaryKey = $row->{$this->setupAssetCollection->getSubjectPrimaryKeyAttribute()};

            if (is_string($primaryKey) || is_int($primaryKey)) {
                $this->addToCollectionPrimaryKey($primaryKey);

                $row->loadAssetConnect($this);
            }
        }

        return $data;
    }

    private function addToCollectionPrimaryKey(int|string $primaryKey): void
    {
        $this->relationsInfo['primaryKeys'][] = $primaryKey;
    }

    private function setEntityType(Entity $entityType): void
    {
        $this->relationsInfo['entityType'] = md5($entityType::class);
    }

    /**
     * Set the collection definition for the assets.
     *
     * @param AssetCollectionDefinitionInterface|string|null $collection The collection definition or class name
     *
     * @throws InvalidArgumentException If the collection is not a valid class or interface
     */
    private function setCollectionDefinition(AssetCollectionDefinitionInterface|string|null $collection): void
    {
        if ($collection === null) {
            $this->relationsInfo['collection'] = null;

            return;
        }

        AssetCollectionDefinitionFactory::validateStringClass($collection);

        $collectionClass = $collection instanceof AssetCollectionDefinitionInterface
            ? $collection::class
            : $collection;

        $this->relationsInfo['collection'] = md5($collectionClass);
    }

    public function fetchForCollection(): void
    {
        if ($this->fetchedForAllCollections) {
            // If we have already fetched assets for all collections, no need to fetch again.
            return;
        }

        $currentCollection = $this->relationsInfo['collection'] ?? '*';

        if (! isset($this->assets[$currentCollection])) {
            $this->assets[$currentCollection] = [];

            $assets = $this->assetModel
                ->groupStart()
                ->when(
                    $currentCollection !== '*',
                    static fn (BaseBuilder $builder) => $builder->where('collection', $currentCollection),
                )
                ->where('entity_type', $this->relationsInfo['entityType'])
                ->whereIn('entity_id', $this->relationsInfo['primaryKeys'])
                ->groupEnd()
                ->findAll();

            foreach ($assets as $asset) {
                $this->addAsset($asset);
            }

            // Mark that we have fetched assets for all collections
            $this->fetchedForAllCollections = $currentCollection === '*';
        }
    }

    public function removeAsset(Asset $asset): void
    {
        if (isset($this->assets[$asset->entity_id])) {
            // Remove the asset from the specific entity's assets by its ID
            $assets = &$this->assets[$asset->entity_id];
            $assets = array_filter($assets, static fn (Asset $a) => $a->id !== $asset->id);
            // Re-index the array to remove gaps in the keys
            $this->assets[$asset->entity_id] = array_values($assets);
        }
    }

    public function removeAssetById(int $id): void
    {
        foreach ($this->assets as &$assetsPerEntity) {
            // Filter out the asset with the given ID
            $assetsPerEntity = array_filter($assetsPerEntity, static fn (Asset $asset) => $asset->id !== $id);
            // Re-index the array to remove gaps in the keys
            $assetsPerEntity = array_values($assetsPerEntity);
        }
    }

    public function addAsset(Asset $asset): void
    {
        if (! isset($this->assets[$asset->entity_id])) {
            // Initialize the array for this entity ID if it doesn't exist
            $this->assets[$asset->entity_id] = [];
        }

        $this->assets[$asset->entity_id][] = $asset;
    }

    /**
     * Get assets for an entity
     *
     * @param Entity                                                $entity     The entity to get assets for
     * @param class-string<AssetCollectionDefinitionInterface>|null $collection The collection to get assets from
     *
     * @return list<Asset> An array of assets
     */
    public function getAssetsForEntity(Entity $entity, ?string $collection = null): array
    {
        $entityId = $entity->{$this->setupAssetCollection->getSubjectPrimaryKeyAttribute()} ?? null;

        if ($entityId === null) {
            return [];
        }

        // Get the entity class name and ID
        $this->setEntityType($entity);
        $this->setCollectionDefinition($collection);
        $this->fetchForCollection();

        $assets = $this->assets[$entityId] ?? [];

        $forCollection = $this->relationsInfo['collection'] ?? null;

        // If a specific collection is requested, filter by it
        if ($forCollection !== null) {
            $assets = array_filter($assets, static fn (Asset $asset) => $asset->collection === $forCollection);
        }

        return $assets;
    }

    /**
     * Delete assets for an entity
     *
     * @param object      $entity     The entity to delete assets for
     * @param string|null $collection The collection to delete assets from
     *
     * @return bool True if assets were deleted, false otherwise
     */
    public function deleteAssetsForEntity(object $entity, ?string $collection = null): bool
    {
        // Get the entity class name and ID
        $entityClass = $entity::class;
        $entityId    = $entity->id ?? null;

        if ($entityId === null) {
            return false;
        }

        // Build the query
        $query = $this->assetModel->where('entity_type', $entityClass)
            ->where('entity_id', $entityId);

        // Filter by collection if provided
        if ($collection !== null) {
            $query->where('collection', $collection);
        }

        // Get the assets to delete their files
        $assets = $query->findAll();

        // Delete the files
        foreach ($assets as $asset) {
            $this->deleteFile($asset->path);
        }

        // Delete the asset records
        return $query->delete() !== false;
    }

    /**
     * Get an asset by ID
     *
     * @param int $id The asset ID
     *
     * @return Asset The asset
     */
    public function getAssetById(int $id): Asset
    {
        $asset = $this->assetModel->find($id);

        if ($asset === null) {
            throw new RuntimeException('Asset not found');
        }

        return new Asset($asset);
    }
}
