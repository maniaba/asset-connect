<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Asset;

use CodeIgniter\Entity\Entity;
use Maniaba\AssetConnect\Asset\Interfaces\AssetCollectionDefinitionInterface;
use Maniaba\AssetConnect\AssetCollection\SetupAssetCollection;
use Maniaba\AssetConnect\AssetConnect;
use Maniaba\AssetConnect\Config\Asset as AssetConfig;
use Maniaba\AssetConnect\Exceptions\AssetException;
use Maniaba\AssetConnect\Jobs\CopyAssetsJob;
use Maniaba\AssetConnect\Models\AssetModel;
use Maniaba\AssetConnect\Storage\StorageManager;
use ReflectionClass;
use Throwable;

/**
 * @internal
 */
final class AssetCopier
{
    /**
     * @param class-string<AssetCollectionDefinitionInterface>|null $collection
     *
     * @return list<Asset>
     */
    public function copyFromEntity(
        Entity $sourceEntity,
        Entity $targetEntity,
        ?string $collection,
        ?string $storage,
        bool $usingQueue = false,
    ): array {
        $setup = $this->setupEntity($sourceEntity);

        /** @var AssetConfig $config */
        $config = config('Asset');
        $model  = AssetModel::init(false)->where([
            'entity_type' => $config->getEntityTypeKey($sourceEntity),
            'entity_id'   => $this->entityId($sourceEntity, $setup),
        ]);
        if ($collection !== null) {
            $model->where('collection', $config->getCollectionKey($collection));
        }

        $sources = $model->orderBy('order', 'ASC')->orderBy('id', 'ASC')->findAll();

        return $this->copy($sources, $targetEntity, null, $storage, $usingQueue);
    }

    /**
     * @param list<Asset>                                                                              $sources
     * @param AssetCollectionDefinitionInterface|class-string<AssetCollectionDefinitionInterface>|null $collection
     *
     * @return list<Asset>
     */
    public function copy(
        array $sources,
        Entity $targetEntity,
        AssetCollectionDefinitionInterface|string|null $collection,
        ?string $storage,
        bool $usingQueue = false,
    ): array {
        $storageManager = StorageManager::make();
        $storage        = $storage === null ? null : trim($storage);
        if ($storage !== null) {
            $storageManager->disk($storage);
        }

        if ($sources === []) {
            return [];
        }

        if (! method_exists($targetEntity, 'assetConnectInstance')) {
            throw AssetException::forInvalidEntity($targetEntity);
        }

        $connection   = $targetEntity->assetConnectInstance();
        $copies       = [];
        $persistences = [];

        foreach ($sources as $index => $source) {
            if ($source->isCopyPending() || ($usingQueue && $source->id <= 0)) {
                $message = 'Copying requires an available source asset.';

                throw new AssetException($message, $message, 409);
            }

            $setup = $this->setupEntity($targetEntity);

            $definition = $collection ?? $source->collection_definition_class;
            if ($definition !== $setup->getCollectionDefinition()::class) {
                $setup->setDefaultCollectionDefinition($definition);
            }

            if ($usingQueue) {
                $definitionClass = new ReflectionClass($setup->getCollectionDefinition());
                if (! $definitionClass->isInstantiable() || ($definitionClass->getConstructor()?->getNumberOfRequiredParameters() ?? 0) > 0) {
                    $message = 'Queued copying requires a collection definition that can be instantiated without arguments.';

                    throw new AssetException($message, $message, 400);
                }
            }

            $copy = Asset::create([
                'entity_type' => $targetEntity,
                'entity_id'   => $this->entityId($targetEntity, $setup),
                'file_name'   => $source->file_name,
                'name'        => $source->name,
                'mime_type'   => $source->mime_type,
                'size'        => $source->size,
                'order'       => $source->order,
                'metadata'    => json_encode($source->metadata->userCustom, JSON_THROW_ON_ERROR),
            ]);

            $fileName = ($setup->getFileNameSanitizer())($source->file_name);
            if (in_array($fileName, ['', '.', '..'], true) || str_contains($fileName, '\\') || preg_match('~[\\x00-\\x1f/]~', $fileName) === 1) {
                throw AssetException::forFileNameNotAllowed($fileName);
            }

            $diskName    = $storage ?? $source->storage;
            $persistence = new AssetPersistenceManager($targetEntity, $copy, $setup, $fileName);
            $persistence->prepareCopy($storageManager->disk($diskName), $diskName);

            $copies[$index]       = $copy;
            $persistences[$index] = $persistence;
        }

        $model = AssetModel::init(false);

        try {
            $batch              = $usingQueue ? bin2hex(random_bytes(16)) : null;
            $variantDirectories = [];

            foreach ($persistences as $index => $persistence) {
                if ($batch === null) {
                    $persistence->stageCopy($sources[$index]);
                } else {
                    $variantDirectories[$index] = $persistence->prepareQueuedCopy($batch);
                }
            }

            foreach ($persistences as $persistence) {
                $persistence->insertAsset($model);
            }

            $sourceIds   = array_map(static fn (Asset $source): int => (int) $source->id, $sources);
            $obsoleteIds = self::obsoleteAssetIds($model, $persistences, $sourceIds, $copies);

            foreach ($persistences as $persistence) {
                $persistence->publish();
            }

            if ($batch !== null) {
                $items = [];

                foreach ($copies as $index => $copy) {
                    $items[] = [
                        'sourceId'         => $sourceIds[$index],
                        'copyId'           => $copy->id,
                        'variantDirectory' => $variantDirectories[$index],
                    ];
                }

                CopyAssetsJob::dispatch($batch, $items);

                return $copies;
            }

            foreach ($persistences as $persistence) {
                $persistence->scheduleVariants();
            }

            // Apply retention in one statement, after the whole copy has succeeded.
            if ($obsoleteIds !== []) {
                self::applyRetention($model, $obsoleteIds);

                if ($connection instanceof AssetConnect) {
                    foreach ($obsoleteIds as $id) {
                        $connection->removeAssetById($id);
                    }
                }
            }
        } catch (Throwable $exception) {
            foreach (array_reverse($persistences) as $persistence) {
                $persistence->discardCopy();
            }

            throw $exception;
        }

        return $copies;
    }

    private function setupEntity(Entity $entity): SetupAssetCollection
    {
        if (! method_exists($entity, 'setupAssetConnect')) {
            throw AssetException::forInvalidEntity($entity);
        }

        $setup = new SetupAssetCollection();
        $entity->setupAssetConnect($setup);

        return $setup;
    }

    private function entityId(Entity $entity, SetupAssetCollection $setup): int
    {
        $id = filter_var($entity->{$setup->getSubjectPrimaryKeyAttribute()}, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 0],
        ]);

        if ($id === false) {
            throw AssetException::forInvalidEntity($entity);
        }

        return $id;
    }

    /**
     * @param list<AssetPersistenceManager> $persistences
     * @param list<int>                     $sourceIds
     * @param list<Asset>                   $copies
     *
     * @return list<int>
     */
    public static function obsoleteAssetIds(AssetModel $model, array $persistences, array $sourceIds, array $copies): array
    {
        $obsoleteIds = [];
        $collections = [];

        foreach ($persistences as $index => $persistence) {
            $collection = $copies[$index]->collection;
            if (! isset($collections[$collection])) {
                $obsoleteIds              = [...$obsoleteIds, ...$persistence->obsoleteAssetIds($model)];
                $collections[$collection] = true;
            }
        }

        $protectedIds = [...$sourceIds, ...array_map(static fn (Asset $asset): int => $asset->id, $copies)];
        if (array_intersect($obsoleteIds, $protectedIds) !== []) {
            $message = 'The destination collection cannot retain all copies without discarding a source or a copy.';

            throw new AssetException($message, $message, 409);
        }

        return $obsoleteIds;
    }

    /**
     * @param list<int> $obsoleteIds
     */
    public static function applyRetention(AssetModel $model, array $obsoleteIds): void
    {
        if ($obsoleteIds !== [] && ! $model->whereIn('id', $obsoleteIds)->delete()) {
            throw AssetException::forDatabaseError($model->errors() ?: ['Could not apply destination collection retention.']);
        }
    }
}
