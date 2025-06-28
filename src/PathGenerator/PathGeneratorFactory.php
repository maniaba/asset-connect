<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\PathGenerator;

use Maniaba\FileConnect\AssetCollection\AssetCollection;
use Maniaba\FileConnect\Config\Asset;
use Maniaba\FileConnect\Exceptions\InvalidArgumentException;

final class PathGeneratorFactory
{
    public static function create(AssetCollection $collection): PathGenerator
    {
        /** @var Asset $config */
        $config             = config('Asset');
        $pathGeneratorClass = $config->defaultPathGenerator;

        if (! class_exists($pathGeneratorClass)) {
            $error = sprintf('Path generator class %s does not exist.', $pathGeneratorClass);

            throw new InvalidArgumentException($error, $error, 500);
        }

        if (! is_subclass_of($pathGeneratorClass, PathGeneratorInterface::class)) {
            $error = sprintf(
                'Path generator class %s must implement %s.',
                $pathGeneratorClass,
                PathGeneratorInterface::class,
            );

            throw new InvalidArgumentException($error, $error, 500);
        }

        return new PathGenerator(new $pathGeneratorClass(), $collection);
    }
}
