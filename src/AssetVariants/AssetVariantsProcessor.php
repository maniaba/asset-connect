<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\AssetVariants;

use Closure;
use Maniaba\AssetConnect\Asset\Asset;
use Maniaba\AssetConnect\AssetVariants\Interfaces\CreateAssetVariantsInterface;
use Maniaba\AssetConnect\Enums\AssetExtension;
use Maniaba\AssetConnect\Exceptions\FileVariantException;
use Override;
use Throwable;

final class AssetVariantsProcessor implements CreateAssetVariantsInterface
{
    public bool $onQueue = true;

    public function __construct(
        private Asset &$asset,
    ) {
    }

    #[Override]
    public function assetVariant(string $name, Closure $closure, AssetExtension|string|null $extension = null): ?AssetVariant
    {
        $variant = $this->asset->metadata->assetVariant->getAssetVariant($name);

        if ($variant === null) {
            log_message('error', 'Asset variant "{name}" not found for asset ID "{asset}".', [
                'name'  => $name,
                'asset' => $this->asset->id,
            ]);

            return null;
        }

        try {
            $closure($variant, $this->asset);
        } catch (Throwable $exception) {
            log_message('error', 'Error processing asset variant "{name}" for asset ID "{asset}": {message}', [
                'name'    => $name,
                'asset'   => $this->asset->id,
                'message' => $exception->getMessage(),
            ]);

            $error = "Failed to process asset variant '{$name}' for asset ID '{$this->asset->id}': " . $exception->getMessage();

            throw new FileVariantException(
                $error,
                $error,
                0,
                $exception,
            );
        }

        if (! file_exists($variant->path)) {
            log_message('error', 'Asset variant file "{path}" does not exist after processing.', [
                'path' => $variant->path,
            ]);

            throw new FileVariantException("Asset variant file '{$variant->path}' does not exist after processing.");
        }

        // Update the size of the variant after processing
        $variant->size      = filesize($variant->path);
        $variant->processed = true;

        // Update the asset
        $this->asset->metadata->assetVariant->updateAssetVariant($variant);

        return $variant;
    }
}
