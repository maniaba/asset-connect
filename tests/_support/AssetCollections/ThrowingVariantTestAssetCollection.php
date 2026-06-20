<?php

declare(strict_types=1);

namespace Tests\Support\AssetCollections;

use Maniaba\AssetConnect\Asset\Asset;
use Maniaba\AssetConnect\Asset\Interfaces\AssetCollectionDefinitionInterface;
use Maniaba\AssetConnect\Asset\Interfaces\AssetCollectionSetterInterface;
use Maniaba\AssetConnect\AssetVariants\Interfaces\AssetVariantsInterface;
use Maniaba\AssetConnect\AssetVariants\Interfaces\CreateAssetVariantsInterface;
use Override;
use Throwable;

final class ThrowingVariantTestAssetCollection implements AssetCollectionDefinitionInterface, AssetVariantsInterface
{
    public static ?Throwable $throwable = null;
    public static bool $variantsCalled  = false;

    public static function reset(): void
    {
        self::$throwable      = null;
        self::$variantsCalled = false;
    }

    #[Override]
    public function definition(AssetCollectionSetterInterface $definition): void
    {
        unset($definition);
    }

    #[Override]
    public function variants(CreateAssetVariantsInterface $variants, Asset $asset): void
    {
        unset($variants, $asset);

        self::$variantsCalled = true;

        if (self::$throwable !== null) {
            throw self::$throwable;
        }
    }
}
