<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Storage\Interfaces;

use Maniaba\AssetConnect\Enums\AssetVisibility;

interface StorageDiskInterface
{
    public function name(): string;

    public function visibility(): AssetVisibility;

    public function write(string $path, string $contents, array $config = []): void;

    /**
     * @param resource $stream
     */
    public function writeStream(string $path, $stream, array $config = []): void;

    public function read(string $path): string;

    /**
     * @return resource
     */
    public function readStream(string $path);

    public function delete(string $path): void;

    public function fileExists(string $path): bool;

    public function fileSize(string $path): int;

    public function mimeType(string $path): string;

    public function lastModified(string $path): int;

    public function publicUrl(string $path): string;

    public function localPath(string $path): ?string;
}
