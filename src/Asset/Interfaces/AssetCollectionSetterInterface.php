<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Asset\Interfaces;

use Maniaba\FileConnect\Enums\AssetExtension;
use Maniaba\FileConnect\Enums\AssetMimeType;
use Maniaba\FileConnect\PathGenerator\Interfaces\PathGeneratorInterface;

interface AssetCollectionSetterInterface
{
    public function allowedExtensions(AssetExtension|string ...$extensions): static;

    public function allowedMimeTypes(AssetMimeType|string ...$mimeTypes): static;

    public function onlyKeepLatest(int $maximumNumberOfItemsInCollection): static;

    public function setMaxFileSize(float|int $maxFileSize): static;

    public function singleFileCollection(): static;

    public function setPathGenerator(PathGeneratorInterface $pathGenerator): static;
}
