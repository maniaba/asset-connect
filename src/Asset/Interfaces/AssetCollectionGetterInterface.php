<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Asset\Interfaces;

use Maniaba\FileConnect\Enums\AssetVisibility;

interface AssetCollectionGetterInterface
{
    public function getVisibility(): AssetVisibility;

    public function getMaximumNumberOfItemsInCollection(): int;

    public function getMaxFileSize(): int;

    public function isSingleFileCollection(): bool;

    public function getAllowedMimeTypes(): array;

    public function getAllowedExtensions(): array;
}
