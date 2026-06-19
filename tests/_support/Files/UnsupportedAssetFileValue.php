<?php

declare(strict_types=1);

namespace Tests\Support\Files;

final readonly class UnsupportedAssetFileValue
{
    public function __construct(private int $size)
    {
    }

    public function getSize(): int
    {
        return $this->size;
    }
}
