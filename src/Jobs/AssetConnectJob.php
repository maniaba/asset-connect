<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Jobs;

use CodeIgniter\Queue\BaseJob;
use CodeIgniter\Queue\Interfaces\JobInterface;
use Maniaba\FileConnect\Asset\Asset;
use Maniaba\FileConnect\AssetCollection\AssetCollectionDefinitionFactory;
use Maniaba\FileConnect\AssetCollection\AssetVariantsProcess;
use Maniaba\FileConnect\Exceptions\AssetException;
use Maniaba\FileConnect\Interfaces\Asset\AssetCollectionDefinitionInterface;
use Maniaba\FileConnect\Models\AssetModel;

/**
 * @property array{assetId: int, definition: class-string<AssetCollectionDefinitionInterface>, definitionArguments: array} $data
 */
final class AssetConnectJob extends BaseJob implements JobInterface
{
    protected int $retryAfter = 60;
    protected int $tries      = 1;
    private ?Asset $asset;
    private AssetCollectionDefinitionInterface $definitionInstance;

    public function process(): void
    {
        if ($this->getAsset() === null) {
            log_message('error', 'AssetJob: Invalid asset ID.');

            throw new AssetException(
                'Invalid storage path or asset ID.',
                'AssetConnectJob: Invalid asset ID.',
            );
        }

        AssetVariantsProcess::run(
            $this->getAsset(),
            $this->getAssetCollectionDefinition(),
        );

        log_message('info', 'Asset variants processing queued successfully for asset ID: {id}', ['id' => $this->getAsset()->id]);

        // Save the asset after processing variants, updating its properties
        $newAsset = new Asset([
            'id'         => $this->getAsset()->id,
            'properties' => $this->getAsset()->properties,
        ]);
        model(AssetModel::class, false)->save($newAsset);
    }

    private function &getAsset(): ?Asset
    {
        if (! isset($this->asset)) {
            $this->asset = model(AssetModel::class, false)->find($this->data['assetId'] ?? 0);
        }

        return $this->asset;
    }

    private function getAssetCollectionDefinition(): AssetCollectionDefinitionInterface
    {
        if (! isset($this->definitionInstance)) {
            $definitionArguments      = $this->data['definitionArguments'] ?? [];
            $this->definitionInstance = AssetCollectionDefinitionFactory::create($this->data['definition'], ...$definitionArguments);
        }

        return $this->definitionInstance;
    }
}
