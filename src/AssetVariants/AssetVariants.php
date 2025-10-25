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
    ) {
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function assetVariant(string $name, Closure $closure, AssetExtension|string|null $extension = null): AssetVariant
    {
        $extension                = $extension instanceof AssetExtension ? $extension->value : $extension;
        $fileNameWithoutExtension = pathinfo($this->asset->file_name, PATHINFO_FILENAME);
        $fileExtension            = $extension ?? pathinfo($this->asset->file_name, PATHINFO_EXTENSION);

        $variantFileName = $fileNameWithoutExtension . '-' . $name . '.' . $fileExtension;

        $variant = new AssetVariant([
            'name'      => $name,
            'path'      => $this->pathGenerator->getPathForVariants() . $variantFileName,
            'size'      => 0, // Size will be updated after writing the file
            'processed' => false,
            'paths'     => [
                'storage_base_directory_path' => $this->pathGenerator->getStoreDirectoryForVariants(),
                'file_relative_path'          => $this->pathGenerator->getFileRelativePathForVariants(),
            ],
        ]);

        $this->asset->metadata->assetVariant->addAssetVariant($variant);

        // Trigger variant.created event
        $variantCreatedEvent = new VariantCreated($variant, $this->asset);
        Events::trigger(VariantCreated::name(), $variantCreatedEvent);

        return $variant;
    }
}
