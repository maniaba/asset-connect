<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\PathGenerator;

use Maniaba\FileConnect\Enums\AssetVisibility;
use Maniaba\FileConnect\Interfaces\Asset\AssetCollectionGetterInterface;

final class DefaultPathGenerator implements PathGeneratorInterface
{
    public function getPath(PathGeneratorHelper $generatorHelper, AssetCollectionGetterInterface $collection): string
    {
        $isProtected = $collection->getVisibility() === AssetVisibility::PROTECTED;
        $basePath    = $isProtected ? WRITEPATH : PUBLICPATH;

        return $basePath . $generatorHelper->getPathString('asssets', $generatorHelper->getDateTime());
    }

    public function getPathForVariants(PathGeneratorHelper $generatorHelper, AssetCollectionGetterInterface $collection): string
    {
        $basePath = $this->getPath($generatorHelper, $collection);

        return $basePath . $generatorHelper->getPathString('variants');
    }
}
