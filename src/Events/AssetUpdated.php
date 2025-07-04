<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Events;

use Maniaba\FileConnect\Asset\Asset;
use Maniaba\FileConnect\Exceptions\AssetException;
use Maniaba\FileConnect\Models\AssetModel;

/**
 * Event fired when an asset is updated
 */
final class AssetUpdated implements AssetEventInterface
{
    /**
     * The asset that was updated
     */
    private ?Asset $asset = null;

    private function __construct(
        private readonly int $assetId,
    ) {
    }

    public static function createFromId(int $assetId): self
    {
        return new self($assetId);
    }

    public function getAsset(): Asset
    {
        if ($this->asset === null) {
            $this->asset = model(AssetModel::class, false)->find($this->assetId);

            if (! $this->asset instanceof Asset) {
                $message = 'Asset not found for ID: ' . $this->assetId;

                throw new AssetException($message, $message);
            }
        }

        return $this->asset;
    }

    public static function name(): string
    {
        return 'asset.updated';
    }
}
