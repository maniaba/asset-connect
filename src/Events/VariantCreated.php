<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Events;

use Maniaba\AssetConnect\Asset\Asset;
use Maniaba\AssetConnect\AssetVariants\AssetVariant;
use Override;

/**
 * Event fired when a variant is created
 */
final class VariantCreated implements AssetEventInterface
{
    /**
     * Constructor
     *
     * @param AssetVariant $variant The variant that was created
     * @param Asset        $asset   The asset that the variant belongs to
     */
    public function __construct(
        private readonly AssetVariant $variant,
        private readonly Asset $asset,
    ) {
    }

    #[Override]
    public function getAsset(): Asset
    {
        return $this->asset;
    }

    public function getVariant(): AssetVariant
    {
        return $this->variant;
    }

    #[Override]
    public static function name(): string
    {
        return 'variant.created';
    }
}
