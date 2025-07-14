<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Events;

use Maniaba\AssetConnect\Asset\Asset;
use Maniaba\AssetConnect\Exceptions\AssetException;
use Maniaba\AssetConnect\Models\AssetModel;
use Override;

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

    #[Override]
    public function getAsset(): Asset
    {
        if ($this->asset === null) {
            $this->asset = AssetModel::init(false)->find($this->assetId);

            if (! $this->asset instanceof Asset) {
                $message = 'Asset not found for ID: ' . $this->assetId;

                throw new AssetException($message, $message);
            }
        }

        return $this->asset;
    }

    #[Override]
    public static function name(): string
    {
        return 'asset.updated';
    }
}
