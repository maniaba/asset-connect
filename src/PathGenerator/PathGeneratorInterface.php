<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\PathGenerator;

use Maniaba\FileConnect\Asset\Interfaces\AssetCollectionGetterInterface;

interface PathGeneratorInterface
{
    // Get the path for the given media, relative to the root storage path.
    public function getPath(PathGeneratorHelper $generatorHelper, AssetCollectionGetterInterface $collection): string;

    // Get the path for conversions of the given media, relative to the root storage path.
    public function getPathForVariants(PathGeneratorHelper $generatorHelper, AssetCollectionGetterInterface $collection): string;

    /**
     * @param string $path The path of the directory that was created.
     */
    public function onCreatedDirectory(string $path): void;
}
