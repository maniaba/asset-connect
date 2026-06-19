<?php

declare(strict_types=1);

namespace Tests\Support\Files;

use CodeIgniter\Files\File;
use Override;

final class FixedSizeFile extends File
{
    public function __construct(string $path, private readonly int $fixedSize)
    {
        parent::__construct($path);
    }

    #[Override]
    public function getSize(): int
    {
        return $this->fixedSize;
    }
}
