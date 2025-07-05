<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\AssetCollection;

use Maniaba\AssetConnect\Asset\Interfaces\AssetCollectionDefinitionInterface;
use Maniaba\AssetConnect\Exceptions\InvalidArgumentException;

final class AssetCollectionDefinitionFactory
{
    /**
     * Validates if the provided collection definition is a string that represents a valid class
     * implementing AssetCollectionDefinitionInterface.
     *
     * @throws InvalidArgumentException if the class does not exist or does not implement the interface.
     */
    public static function validateStringClass(
        AssetCollectionDefinitionInterface|string $collectionDefinition,
    ): void {
        if (is_string($collectionDefinition) && (! class_exists($collectionDefinition) || ! is_subclass_of($collectionDefinition, AssetCollectionDefinitionInterface::class))) {
            throw new InvalidArgumentException(sprintf(
                'Expected a class implementing %s, got %s',
                AssetCollectionDefinitionInterface::class,
                $collectionDefinition,
            ));
        }
    }

    public static function create(AssetCollectionDefinitionInterface|string $collectionDefinition, ...$args): AssetCollectionDefinitionInterface
    {
        self::validateStringClass($collectionDefinition);

        if (is_string($collectionDefinition)) {
            return new $collectionDefinition(...$args);
        }

        return $collectionDefinition;
    }
}
