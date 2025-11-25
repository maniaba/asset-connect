<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Jobs;

use CodeIgniter\Events\Events;
use CodeIgniter\Queue\BaseJob;
use CodeIgniter\Queue\Interfaces\JobInterface;
use Maniaba\AssetConnect\Asset\Asset;
use Maniaba\AssetConnect\Asset\AssetPersistenceManager;
use Maniaba\AssetConnect\Asset\Interfaces\AssetCollectionDefinitionInterface;
use Maniaba\AssetConnect\AssetCollection\AssetCollectionDefinitionFactory;
use Maniaba\AssetConnect\AssetVariants\AssetVariantsProcess;
use Maniaba\AssetConnect\AssetVariants\Interfaces\AssetVariantsInterface;
use Maniaba\AssetConnect\Events\AssetUpdated;
use Maniaba\AssetConnect\Exceptions\AssetException;
use Maniaba\AssetConnect\Models\AssetModel;
use Maniaba\AssetConnect\Pending\PendingAssetManager;
use Override;

/**
 * @property array{assetId: int, definition: class-string<AssetCollectionDefinitionInterface>, definitionArguments: array} $data
 */
final class AssetConnectJob extends BaseJob implements JobInterface
{
    protected int $retryAfter = 60;
    protected int $tries      = 1;
    private ?Asset $asset     = null;
    private AssetCollectionDefinitionInterface&AssetVariantsInterface $definitionInstance;

    #[Override]
    public function process(): void
    {
        if ($this->getAsset() === null) {
            log_message('error', 'AssetJob: Invalid asset ID.');

            throw new AssetException(
                'Invalid storage path or asset ID.',
                'AssetConnectJob: Invalid asset ID.',
            );
        }

        $asset = $this->getAsset();

        AssetVariantsProcess::run(
            $asset,
            $this->getAssetCollectionDefinition(),
        );

        log_message('info', 'Asset variants processing queued successfully for asset ID: {id}', ['id' => $this->getAsset()->id]);

        // Save the asset after processing variants, updating its properties
        $newAsset = Asset::create([
            'id'       => $this->getAsset()->id,
            'metadata' => $this->getAsset()->metadata,
        ]);

        AssetModel::init(false)->save($newAsset);

        // Trigger the asset updated event
        Events::trigger(AssetUpdated::name(), AssetUpdated::createFromId($this->getAsset()->id));

        log_message('info', 'Asset with ID {id} has been saved after processing variants.', ['id' => $this->getAsset()->id]);

        // Clean up garbage assets soft-deleted from the database
        $this->cleanGarbage();
    }

    private function getAsset(): ?Asset
    {
        if (! isset($this->asset)) {
            $this->asset = AssetModel::init(false)->find($this->data['assetId'] ?? 0);
        }

        return $this->asset;
    }

    private function getAssetCollectionDefinition(): AssetCollectionDefinitionInterface&AssetVariantsInterface
    {
        if (! isset($this->definitionInstance)) {
            $definitionArguments = $this->data['definitionArguments'] ?? [];

            /** @var AssetCollectionDefinitionInterface&AssetVariantsInterface $definitionInstance */
            $definitionInstance = AssetCollectionDefinitionFactory::create($this->data['definition'], ...$definitionArguments);

            if (! $definitionInstance instanceof AssetVariantsInterface) {
                throw new AssetException(
                    'Invalid asset collection definition provided.',
                    'AssetConnectJob: Invalid asset collection definition.',
                );
            }

            $this->definitionInstance = $definitionInstance;
        }

        return $this->definitionInstance;
    }

    // clean garbage, soft delete assets from database
    public function cleanGarbage(): void
    {
        $deletedAssets = AssetModel::init(false)->onlyDeleted()->findAll(1000);
        if ($deletedAssets === []) {
            return;
        }

        foreach ($deletedAssets as $asset) {
            $variants = $asset->metadata->assetVariant->getVariants();

            foreach ($variants as $variant) {
                AssetPersistenceManager::removeStoragePath($variant->path);
            }

            AssetPersistenceManager::removeStoragePath($asset->path);

            AssetModel::init(false)->delete((int) $asset->id, true);

            log_message('info', 'Asset with ID {id} has been permanently deleted.', ['id' => $asset->id]);
        }

        // Clean expired pending assets as well from default pending storage
        $manager = PendingAssetManager::make();
        $manager->cleanExpiredPendingAssets();
    }
}
