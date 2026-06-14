<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Storage;

use League\Flysystem\FilesystemOperator;
use League\Flysystem\Visibility;
use League\Flysystem\WhitespacePathNormalizer;
use Maniaba\AssetConnect\Enums\AssetVisibility;
use Maniaba\AssetConnect\Storage\Interfaces\StorageDiskInterface;
use Override;

final readonly class FlysystemStorageDisk implements StorageDiskInterface
{
    private WhitespacePathNormalizer $pathNormalizer;

    public function __construct(
        private string $name,
        private FilesystemOperator $filesystem,
        private AssetVisibility $visibility,
        private ?string $localRoot = null,
    ) {
        $this->pathNormalizer = new WhitespacePathNormalizer();
    }

    #[Override]
    public function name(): string
    {
        return $this->name;
    }

    #[Override]
    public function visibility(): AssetVisibility
    {
        return $this->visibility;
    }

    #[Override]
    public function write(string $path, string $contents, array $config = []): void
    {
        $this->filesystem->write($this->normalizePath($path), $contents, $this->normalizeConfig($config));
    }

    /**
     * @param resource $stream
     */
    #[Override]
    public function writeStream(string $path, $stream, array $config = []): void
    {
        $this->filesystem->writeStream($this->normalizePath($path), $stream, $this->normalizeConfig($config));
    }

    #[Override]
    public function read(string $path): string
    {
        return $this->filesystem->read($this->normalizePath($path));
    }

    /**
     * @return resource
     */
    #[Override]
    public function readStream(string $path)
    {
        return $this->filesystem->readStream($this->normalizePath($path));
    }

    #[Override]
    public function delete(string $path): void
    {
        $path = $this->normalizePath($path);

        if (! $this->filesystem->fileExists($path)) {
            return;
        }

        $this->filesystem->delete($path);
    }

    #[Override]
    public function fileExists(string $path): bool
    {
        return $this->filesystem->fileExists($this->normalizePath($path));
    }

    #[Override]
    public function fileSize(string $path): int
    {
        return $this->filesystem->fileSize($this->normalizePath($path));
    }

    #[Override]
    public function mimeType(string $path): string
    {
        return $this->filesystem->mimeType($this->normalizePath($path));
    }

    #[Override]
    public function lastModified(string $path): int
    {
        return $this->filesystem->lastModified($this->normalizePath($path));
    }

    #[Override]
    public function publicUrl(string $path): string
    {
        return $this->filesystem->publicUrl($this->normalizePath($path));
    }

    #[Override]
    public function localPath(string $path): ?string
    {
        if ($this->localRoot === null) {
            return null;
        }

        $path = $this->normalizePath($path);

        $localPath = rtrim($this->localRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
        $directory = dirname($localPath);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        return $localPath;
    }

    private function normalizePath(string $path): string
    {
        return ltrim($this->pathNormalizer->normalizePath(str_replace('\\', '/', $path)), '/');
    }

    private function normalizeConfig(array $config): array
    {
        $visibility = $config['visibility'] ?? null;

        if ($visibility instanceof AssetVisibility) {
            $visibility = $visibility->value;
        }

        if ($visibility === AssetVisibility::PROTECTED->value) {
            $config['visibility'] = Visibility::PRIVATE;
        } elseif ($visibility === AssetVisibility::PUBLIC->value) {
            $config['visibility'] = Visibility::PUBLIC;
        }

        return $config;
    }
}
