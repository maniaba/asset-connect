<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Asset\Interfaces;

interface AssetDefinitionInterface
{
    /**
     * Sets the name of the asset.
     *
     * @param string $name The name to set for the asset.
     */
    public function usingName(string $name): self;

    /**
     * Sets the file name of the asset.
     *
     * @param string $fileName The file name to set for the asset.
     */
    public function usingFileName(string $fileName): self;

    /**
     * Sets whether to preserve the original file.
     *
     * @param bool $preserveOriginal Whether to preserve the original file.
     */
    public function preservingOriginal(bool $preserveOriginal = true): self;

    /**
     * Sets the order of the asset.
     *
     * @param int $order The order to set for the asset.
     */
    public function setOrder(int $order): self;

    /**
     * Adds custom properties to the asset.
     *
     * @param array<string, mixed> $customProperties An associative array of custom properties.
     */
    public function withCustomProperties(array $customProperties): self;

    /**
     * Adds a custom property to the asset.
     *
     * @param string $key   The key for the custom property.
     * @param mixed  $value The value for the custom property.
     */
    public function withCustomProperty(string $key, mixed $value): self;
}
