<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\PathGenerator;

use Maniaba\AssetConnect\AssetCollection\AssetCollection;

final class PathGeneratorFactory
{
    public static function create(AssetCollection $collection, string $subdirectory = ''): PathGenerator
    {
        return new PathGenerator($collection, $subdirectory);
    }
}
