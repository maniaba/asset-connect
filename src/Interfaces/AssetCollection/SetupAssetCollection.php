<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Interfaces\AssetCollection;

use Closure;
use Maniaba\FileConnect\Interfaces\Asset\AssetCollectionInterface;
use Maniaba\FileConnect\PathGenerator\PathGeneratorInterface;

interface SetupAssetCollection
{
    public function setCollectionDefinition(AssetCollectionInterface|string $collectionDefinition): static;

    public function setPathGenerator(PathGeneratorInterface $pathGenerator): static;

    /**
     * Set a closure to sanitize file names.
     *
     * @param Closure(string $fileName): string $sanitizer
     */
    public function setFileNameSanitizer(Closure $sanitizer): static;

    /**
     * Set whether to preserve the original file.
     */
    public function setPreserveOriginal(bool $preserve): static;
}
