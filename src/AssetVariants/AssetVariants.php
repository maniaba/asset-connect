<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\AssetVariants;

use Closure;
use CodeIgniter\Events\Events;
use Maniaba\AssetConnect\Asset\Asset;
use Maniaba\AssetConnect\AssetVariants\Interfaces\CreateAssetVariantsInterface;
use Maniaba\AssetConnect\Enums\AssetExtension;
use Maniaba\AssetConnect\Events\VariantCreated;
use Maniaba\AssetConnect\PathGenerator\PathGenerator;
use Override;

final class AssetVariants implements CreateAssetVariantsInterface
{
    public bool $onQueue = false;

    public function __construct(
        private readonly PathGenerator $pathGenerator,
        private readonly Asset $asset,
        private readonly ?string $fileName = null,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function assetVariant(string $name, Closure $closure, AssetExtension|string|null $extension = null): AssetVariant
    {
        $extension                = $extension instanceof AssetExtension ? $extension->value : $extension;
        $fileName                 = $this->fileName ?? $this->asset->file_name;
        $fileNameWithoutExtension = pathinfo($fileName, PATHINFO_FILENAME);
        $fileExtension            = $extension ?? pathinfo($fileName, PATHINFO_EXTENSION);

        $variantFileName = $fileNameWithoutExtension . '-' . $name . '.' . $fileExtension;

        $variant = new AssetVariant([
            'name'      => $name,
            'storage'   => $this->asset->storage,
            'path'      => $this->pathGenerator->getPathForVariants() . $variantFileName,
            'size'      => 0, // Size will be updated after writing the file
            'processed' => false,
        ]);

        $this->asset->metadata->assetVariant->addAssetVariant($variant);

        // Trigger variant.created event
        $variantCreatedEvent = new VariantCreated($variant, $this->asset);
        Events::trigger(VariantCreated::name(), $variantCreatedEvent);

        return $variant;
    }
}
