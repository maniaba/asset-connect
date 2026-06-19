<?php

declare(strict_types=1);

namespace Tests\Support\PathGenerators;

use Maniaba\AssetConnect\Asset\Interfaces\AssetCollectionGetterInterface;
use Maniaba\AssetConnect\PathGenerator\Interfaces\PathGeneratorInterface;
use Maniaba\AssetConnect\PathGenerator\PathGeneratorHelper;
use Override;

final class TestPathGenerator implements PathGeneratorInterface
{
    #[Override]
    public function getFileRelativePath(PathGeneratorHelper $generatorHelper, AssetCollectionGetterInterface $collection): string
    {
        unset($generatorHelper, $collection);

        return 'relative/path/';
    }

    #[Override]
    public function getPath(PathGeneratorHelper $generatorHelper, AssetCollectionGetterInterface $collection): string
    {
        unset($generatorHelper, $collection);

        return 'relative/path/';
    }

    #[Override]
    public function getFileRelativePathForVariants(PathGeneratorHelper $generatorHelper, AssetCollectionGetterInterface $collection): string
    {
        unset($generatorHelper, $collection);

        return 'relative/path/variants/';
    }

    #[Override]
    public function getPathForVariants(PathGeneratorHelper $generatorHelper, AssetCollectionGetterInterface $collection): string
    {
        unset($generatorHelper, $collection);

        return 'relative/path/variants/';
    }
}
