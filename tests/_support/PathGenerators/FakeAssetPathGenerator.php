<?php

declare(strict_types=1);

namespace Tests\Support\PathGenerators;

use Maniaba\AssetConnect\Asset\Interfaces\AssetCollectionGetterInterface;
use Maniaba\AssetConnect\PathGenerator\Interfaces\PathGeneratorInterface;
use Maniaba\AssetConnect\PathGenerator\PathGeneratorHelper;
use Override;

final readonly class FakeAssetPathGenerator implements PathGeneratorInterface
{
    public function __construct(private string $directory)
    {
    }

    #[Override]
    public function getFileRelativePath(PathGeneratorHelper $generatorHelper, AssetCollectionGetterInterface $collection): string
    {
        return $this->directory;
    }

    #[Override]
    public function getPath(PathGeneratorHelper $generatorHelper, AssetCollectionGetterInterface $collection): string
    {
        return $this->getFileRelativePath($generatorHelper, $collection);
    }

    #[Override]
    public function getFileRelativePathForVariants(PathGeneratorHelper $generatorHelper, AssetCollectionGetterInterface $collection): string
    {
        return $this->directory . '/variants';
    }

    #[Override]
    public function getPathForVariants(PathGeneratorHelper $generatorHelper, AssetCollectionGetterInterface $collection): string
    {
        return $this->getFileRelativePathForVariants($generatorHelper, $collection);
    }
}
