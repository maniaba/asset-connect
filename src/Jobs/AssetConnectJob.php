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

/**
 * @property array{storagePath: string, assetId: int, definition: class-string<AssetCollectionDefinitionInterface>, definitionArguments: array} $data
 */
final class AssetConnectJob extends BaseJob implements JobInterface
{
    protected int $retryAfter = 60;
    protected int $tries      = 3;
    private ?Asset $asset;
    private AssetCollectionDefinitionInterface $definitionInstance;

    public function process(): void
    {
        if ($this->storePath() === '' || $this->getAsset() === null) {
            log_message('error', 'AssetJob: Invalid storage path or asset ID.');

            throw new AssetException('Invalid storage path or asset ID.');
        }

        AssetVariantsProcess::run(
            $this->getAssetCollectionDefinition(),
            $this->storePath(),
            $this->getAsset(),
        );
    }

    private function &getAsset(): ?Asset
    {
        if (! isset($this->asset)) {
            $this->asset = model(Asset::class)->find($this->data['assetId'] ?? 0);
        }

        return $this->asset;
    }

    private function storePath(): string
    {
        return $this->data['storagePath'] ?? '';
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
