<?php

declare(strict_types=1);

namespace Tests\Support\Files;

use CodeIgniter\Files\File;
use Override;

final class UnreadableFile extends File
{
    public function __construct(private readonly string $realPath, private readonly int $fixedSize)
    {
        parent::__construct($realPath);
    }

    #[Override]
    public function getRealPath(): string
    {
        return $this->realPath;
    }

    #[Override]
    public function getSize(): int
    {
        return $this->fixedSize;
    }
}
