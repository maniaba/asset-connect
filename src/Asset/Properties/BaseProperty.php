<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Asset\Properties;

use InvalidArgumentException;
use JsonSerializable;

abstract class BaseProperty implements JsonSerializable
{
    abstract public static function getName(): string;

    public function __construct(protected array $properties = [])
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

    public function getAll(): array
    {
        return $this->properties;
    }

    public function remove(string $key): void
    {
        unset($this->properties[$key]);
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

        // @phpstan-ignore-next-line
        return new static($properties);
    }
}
