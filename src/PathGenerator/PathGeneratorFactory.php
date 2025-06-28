<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\PathGenerator;

use Maniaba\FileConnect\AssetCollection\AssetCollection;

final class PathGeneratorFactory
{
    public static function create(AssetCollection $collection): PathGenerator
    {
        return new PathGenerator($collection);
    }
}
