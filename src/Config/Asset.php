<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Entity\Entity;
use InvalidArgumentException;
use Maniaba\AssetConnect\Asset\Interfaces\AssetCollectionDefinitionInterface;
use Maniaba\AssetConnect\AssetCollection\AssetCollectionDefinitionFactory;
use Maniaba\AssetConnect\AssetCollection\DefaultAssetCollection;
use Maniaba\AssetConnect\AssetVariants\AssetVariantsProcess;
use Maniaba\AssetConnect\Jobs\AssetConnectJob;
use Maniaba\AssetConnect\Models\AssetModel;
use Maniaba\AssetConnect\PathGenerator\DefaultPathGenerator;
use Maniaba\AssetConnect\PathGenerator\Interfaces\PathGeneratorInterface;
use Maniaba\AssetConnect\Pending\DefaultPendingStorage;
use Maniaba\AssetConnect\Pending\Interfaces\PendingSecurityTokenInterface;
use Maniaba\AssetConnect\Pending\Interfaces\PendingStorageInterface;
use Maniaba\AssetConnect\Pending\PendingSecurityToken\SessionPendingSecurityToken;
use Maniaba\AssetConnect\UrlGenerator\DefaultUrlGenerator;
use Maniaba\AssetConnect\UrlGenerator\Interfaces\UrlGeneratorInterface;

class Asset extends BaseConfig
{
    /**
     * --------------------------------------------------------------------
     * Entity type definitions for Asset Connect
     * --------------------------------------------------------------------
     * Define the entity types and their primary keys for Asset Connect.
     * This helps Asset Connect to associate assets with different entity types.
     *
     * @var array<class-string<Entity>, string>
     */
    public array $entityKeyDefinitions = [
        // example:
        // Entity::class => 'basic_entity',
    ];

    /**
     * --------------------------------------------------------------------
     * Collection Definitions for Asset Connect
     * --------------------------------------------------------------------
     * Define the collection definitions for Asset Connect.
     * This helps Asset Connect to manage different asset collections.
     * Use a unique string to identify each collection definition.
     *
     * @var array<class-string<AssetCollectionDefinitionInterface>, string>
     */
    public array $collectionKeyDefinitions = [
        // example:
        // DefaultAssetCollection::class => 'default_collection',
    ];

    /**
     * --------------------------------------------------------------------
     * Customize the DB group used for each model
     * --------------------------------------------------------------------
     */
    public ?string $DBGroup = null;

    /**
     * --------------------------------------------------------------------
     * Default Asset Collection
     * --------------------------------------------------------------------
     * This is the default collection that will be used when no specific
     * collection is provided during asset creation.
     *
     * You can change this to any class that implements the AssetCollectionInterface.
     *
     * @var class-string<AssetCollectionDefinitionInterface>
     */
    public string $defaultCollection = DefaultAssetCollection::class;

    /**
     * --------------------------------------------------------------------
     * Default Path Generator
     * --------------------------------------------------------------------
     * This is the default path generator that will be used to generate
     * the storage paths for assets.
     *
     * You can change this to any class that implements the PathGeneratorInterface.
     * Also allows you to create custom path generators for specific AssetCollectionInterface overriding method pathGenerator().
     *
     * @var class-string<PathGeneratorInterface>
     */
    public string $defaultPathGenerator = DefaultPathGenerator::class;

    /**
     * --------------------------------------------------------------------
     * Default URL Generator
     * --------------------------------------------------------------------
     * This is the default URL generator that will be used to generate
     * the URLs for assets.
     *
     * You can change this to any class that implements the UrlGeneratorInterface.
     *
     * @var class-string<UrlGeneratorInterface>|null If null, the default URL generator will be used and routes not registered.
     */
    public ?string $defaultUrlGenerator = DefaultUrlGenerator::class;

    /**
     * --------------------------------------------------------------------
     * Pending Assets Storage
     * --------------------------------------------------------------------
     * This is the class that will be used to store pending assets.
     * You can change this to any class that implements the PendingStorageInterface.
     *
     * @var class-string<PendingStorageInterface>
     */
    public string $pendingStorage = DefaultPendingStorage::class;

    /**
     * --------------------------------------------------------------------
     * Pending Assets Security Token
     * --------------------------------------------------------------------
     *
     * This is the class that will be used to generate and validate
     * security tokens for pending assets.
     * Security tokens help to ensure that only authorized/uploading user can access the pending assets.
     *
     * You can change this to any class that implements the PendingSecurityTokenInterface.
     *
     * Available options:
     * - SessionPendingSecurityToken: Uses session to store the token.
     * - CookiePendingSecurityToken: Uses cookies to store the token.
     * - Null: Disables security token validation for pending assets.
     *
     * @var class-string<PendingSecurityTokenInterface>|null If null, no security token will be used.
     */
    public ?string $pendingSecurityToken = SessionPendingSecurityToken::class;

    /**
     * --------------------------------------------------------------------
     * Customize Name of Asset Table
     * --------------------------------------------------------------------
     * Only change if you want to rename the default Asset Connect table names.
     *
     * It may be necessary to change the names of the tables for
     * security reasons, to prevent the conflict of table names,
     * the internal policy of the companies or any other reason.
     *
     * - Assets: The table that stores the asset metadata.
     *
     * @var array<string, string>
     */
    public array $tables = [
        'assets' => 'assets',
    ];

    /**
     * --------------------------------------------------------------------
     * Asset Model
     * --------------------------------------------------------------------
     * This is the model that will be used to interact with the assets table.
     * You can change this to any model that extends the AssetModel.
     *
     * @var class-string<AssetModel>
     */
    public string $assetModel = AssetModel::class;

    /**
     * --------------------------------------------------------------------
     * Queue Name
     * --------------------------------------------------------------------
     * This is the name of the queue that will be used for processing
     * asset manipulations and variants.
     */
    public array $queue = [
        'name'       => AssetVariantsProcess::QUEUE_NAME,
        'jobHandler' => [
            'name'  => AssetVariantsProcess::JOB_HANDLER,
            'class' => AssetConnectJob::class,
        ],
    ];

    /**
     * --------------------------------------------------------------------
     * Methods to get entity and collection keys and classes
     * --------------------------------------------------------------------
     * These methods help to retrieve the entity type keys and collection keys
     * based on the provided entity or collection class names or instances.
     *
     * @throws InvalidArgumentException
     */
    final public function getEntityTypeKey(Entity|string $entityType): string
    {
        if ($entityType instanceof Entity || class_exists($entityType)) {
            $entityType = is_string($entityType) ? $entityType : $entityType::class;

            $entityKey = $this->entityKeyDefinitions[$entityType] ?? null;

            if ($entityKey === null) {
                throw new InvalidArgumentException("Entity key for entity class '{$entityType}' is not registered in asset entity definitions.");
            }

            return $entityKey;
        }

        // search entity type key from config
        $entityKey = in_array($entityType, $this->entityKeyDefinitions, true);
        if ($entityKey === false) {
            throw new InvalidArgumentException("Entity class '{$entityType}' is not registered in asset entity definitions.");
        }

        return $entityType;
    }

    /**
     * Retrieves the entity class name associated with the given entity key.
     *
     * @param string $entityKey The key used to identify the entity class.
     *                          This must match an existing key in the entity key
     *                          definitions.
     *
     * @return class-string<Entity> The fully qualified class name corresponding to the specified
     *                              entity key.
     *
     * @throws InvalidArgumentException If the provided entity key is not registered
     *                                  in the entity key definitions.
     */
    final public function getEntityClassFromKey(string $entityKey): string
    {
        $entityClass = array_search($entityKey, $this->entityKeyDefinitions, true);

        if ($entityClass === false) {
            throw new InvalidArgumentException("Entity class for entity type '{$entityKey}' is not registered in asset entity definitions.");
        }

        return $entityClass;
    }

    /**
     * Retrieves the collection key associated with the given collection definition
     * or class name.
     *
     * @param AssetCollectionDefinitionInterface|string $collection The collection definition
     *                                                              instance or the fully qualified
     *                                                              class name of the collection. This
     *                                                              must either implement the
     *                                                              AssetCollectionDefinitionInterface
     *                                                              or be a registered class in asset
     *                                                              collection definitions.
     *
     * @return string The collection key corresponding to the specified collection definition
     *                or class name.
     *
     * @throws InvalidArgumentException If the provided collection is not registered in
     *                                  the asset collection definitions or does not conform to
     *                                  the required interface.
     */
    final public function getCollectionKey(AssetCollectionDefinitionInterface|string $collection): string
    {
        if ($collection instanceof AssetCollectionDefinitionInterface || (class_exists($collection) && is_subclass_of($collection, AssetCollectionDefinitionInterface::class))) {
            $collection = is_string($collection) ? $collection : $collection::class;
            AssetCollectionDefinitionFactory::validateStringClass($collection);

            $collectionKey = $this->collectionKeyDefinitions[$collection] ?? null;

            if ($collectionKey === null) {
                throw new InvalidArgumentException("Collection key for collection class '{$collection}' is not registered in asset collection definitions.");
            }

            return $collectionKey;
        }

        // search collection key from config
        $collectionKey = in_array($collection, $this->collectionKeyDefinitions, true);
        if ($collectionKey === false) {
            throw new InvalidArgumentException("Collection class '{$collection}' is not registered in asset collection definitions.");
        }

        return $collection;
    }

    /**
     * Retrieves the collection class name associated with the given collection key.
     *
     * @param string $collectionKey The key used to identify the collection class.
     *                              This must match an existing key in the collection
     *                              key definitions.
     *
     * @return class-string<AssetCollectionDefinitionInterface> The fully qualified class name corresponding to the specified
     *                                                          collection key.
     *
     * @throws InvalidArgumentException If the provided collection key is not registered
     *                                  in the collection key definitions.
     */
    final public function getCollectionClassFromKey(string $collectionKey): string
    {
        $collectionClass = array_search($collectionKey, $this->collectionKeyDefinitions, true);

        if ($collectionClass === false) {
            throw new InvalidArgumentException("Collection class for collection key '{$collectionKey}' is not registered in asset collection definitions.");
        }

        return $collectionClass;
    }
}
