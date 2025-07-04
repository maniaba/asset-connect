<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Events;

use Maniaba\FileConnect\Asset\Asset;
use Override;

/**
 * Event fired when an asset is deleted
 */
final class AssetDeleted implements AssetEventInterface
{
    private function __construct(
        private readonly Asset $asset,
    ) {
    }

    #[Override]
    public function getAsset(): Asset
    {
        return $this->asset;
    }

    public static function createFromAsset(Asset $asset): self
    {
        return new self($asset);
    }

    #[Override]
    public static function name(): string
    {
        return 'asset.deleted';
    }
}
