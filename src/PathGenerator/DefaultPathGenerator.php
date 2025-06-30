<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\PathGenerator;

use Maniaba\FileConnect\Asset\Interfaces\AssetCollectionGetterInterface;
use Maniaba\FileConnect\Enums\AssetVisibility;

final class DefaultPathGenerator implements PathGeneratorInterface
{
    private string $path;

    public function getPath(PathGeneratorHelper $generatorHelper, AssetCollectionGetterInterface $collection): string
    {
        if (isset($this->path)) {
            return $this->path;
        }

        $isProtected = $collection->getVisibility() === AssetVisibility::PROTECTED;
        $basePath    = $isProtected ? WRITEPATH : realpath(ROOTPATH . 'public') . DIRECTORY_SEPARATOR;

        return $this->path = $basePath . 'assets' . DIRECTORY_SEPARATOR . $generatorHelper->getDateTime() . DIRECTORY_SEPARATOR;
    }

    public function getPathForVariants(PathGeneratorHelper $generatorHelper, AssetCollectionGetterInterface $collection): string
    {
        $basePath = $this->path ?? $this->getPath($generatorHelper, $collection);

        return $basePath . 'variants';
    }

    public function onCreatedDirectory(string $path): void
    {
        // Recrusively create empty index.html file to prevent directory listing
        log_message('debug', "Creating path '{$path}' for assets.");
    }
}
