<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\PathGenerator;

use Maniaba\FileConnect\Enums\AssetVisibility;
use Maniaba\FileConnect\Interfaces\Asset\AssetCollectionGetterInterface;

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

        return $this->path = $basePath . $generatorHelper->getPathString('assets', $generatorHelper->getDateTime());
    }

    public function getPathForVariants(PathGeneratorHelper $generatorHelper, AssetCollectionGetterInterface $collection): string
    {
        $basePath = $this->path ?? $this->getPath($generatorHelper, $collection);

        return $basePath . $generatorHelper->getPathString('variants');
    }
}
