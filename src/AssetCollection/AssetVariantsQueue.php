<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\AssetCollection;

use Closure;
use Maniaba\FileConnect\Asset\Asset;
use Maniaba\FileConnect\Asset\AssetVariant;
use Maniaba\FileConnect\Exceptions\FileVariantException;
use Maniaba\FileConnect\Interfaces\AssetCollection\CreateAssetVariantsInterface;
use Throwable;

final class AssetVariantsQueue implements CreateAssetVariantsInterface
{
    public bool $isQueue = true;

    public function __construct(
        private Asset &$asset,
    ) {
    }

    public function assetVariant(string $name, Closure $closure): ?AssetVariant
    {
        $variant = $this->asset->properties->fileVariant->getAssetVariant($name);

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

        // Update the asset variant in the properties
        $properties = $this->asset->properties;
        $properties->fileVariant->updateAssetVariant($variant);

        // Update the asset
        $this->asset->properties = $properties;

        return $variant;
    }
}
