<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Asset\Properties;

use InvalidArgumentException;
use JsonSerializable;

abstract class BaseProperties implements JsonSerializable
{
    abstract public static function getName(): string;

    public function __construct(private array $properties)
    {
    }

    public function jsonSerialize(): array
    {
        return [static::getName() => $this->properties];
    }

    public function set(string $key, mixed $value): void
    {
        $this->properties[$key] = $value;
    }

    public function get(string $key): mixed
    {
        return $this->properties[$key] ?? null;
    }

    public static function create(array $properties): static
    {
        $properties = $properties[static::getName()] ?? [];
        if (! is_array($properties)) {
            throw new InvalidArgumentException(sprintf(
                'Expected an array for properties, got %s',
                gettype($properties),
            ));
        }

        return new static($properties);
    }
}
