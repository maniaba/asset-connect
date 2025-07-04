<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Events;

use Maniaba\FileConnect\Asset\Asset;

/**
 * Event fired when an asset is deleted
 */
final class AssetDeleted implements AssetEventInterface
{
    private function __construct(
        private readonly Asset $asset,
    ) {
    }

    public function getAsset(): Asset
    {
        return $this->asset;
    }

    public static function createFromAsset(Asset $asset): self
    {
        return new self($asset);
    }

    public static function name(): string
    {
        return 'asset.deleted';
    }
}
