<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Storage;

use Maniaba\AssetConnect\Exceptions\FileException;
use Maniaba\AssetConnect\Storage\Interfaces\StorageDiskInterface;
use Throwable;

final class TemporaryStorageFile
{
    public static function copyFromStorage(
        StorageDiskInterface $disk,
        string $path,
        ?string $extension = null,
        ?string $directory = null,
        string $prefix = 'asset_connect_',
    ): string {
        if (! $disk->fileExists($path)) {
            throw FileException::forFileNotFound($disk->name() . ':' . $path);
        }

        $targetPath = self::createPath($directory ?? sys_get_temp_dir(), $prefix, $extension);
        $source     = null;
        $target     = null;

        try {
            $source = $disk->readStream($path);
            if (! is_resource($source)) {
                throw FileException::forCannotCopyFile($disk->name() . ':' . $path, $targetPath);
            }

            $target = fopen($targetPath, 'wb');
            if ($target === false) {
                throw FileException::forCannotCopyFile($disk->name() . ':' . $path, $targetPath);
            }

            if (stream_copy_to_stream($source, $target) === false) {
                throw FileException::forCannotCopyFile($disk->name() . ':' . $path, $targetPath);
            }
        } catch (Throwable $exception) {
            if (is_file($targetPath)) {
                @unlink($targetPath);
            }

            if ($exception instanceof FileException) {
                throw $exception;
            }

            throw FileException::forCannotCopyFile($disk->name() . ':' . $path, $targetPath);
        } finally {
            if (is_resource($target)) {
                fclose($target);
            }

            if (is_resource($source)) {
                fclose($source);
            }
        }

        return $targetPath;
    }

    /**
     * @template TReturn
     *
     * @param callable(string): TReturn $callback
     *
     * @return TReturn
     */
    public static function withTemporaryFile(
        StorageDiskInterface $disk,
        string $path,
        callable $callback,
        ?string $extension = null,
        ?string $directory = null,
        string $prefix = 'asset_connect_',
    ): mixed {
        $temporaryFile = self::copyFromStorage($disk, $path, $extension, $directory, $prefix);

        try {
            return $callback($temporaryFile);
        } finally {
            if (is_file($temporaryFile)) {
                @unlink($temporaryFile);
            }
        }
    }

    private static function createPath(string $directory, string $prefix, ?string $extension): string
    {
        if (! is_dir($directory) || ! is_writable($directory)) {
            throw FileException::forInvalidFile($directory);
        }

        $temporaryFile = tempnam($directory, $prefix);
        if ($temporaryFile === false) {
            throw FileException::forInvalidFile($directory);
        }

        $extension = self::normalizeExtension($extension);
        if ($extension === '') {
            return $temporaryFile;
        }

        $targetPath = $temporaryFile . '.' . $extension;
        if (! rename($temporaryFile, $targetPath)) {
            @unlink($temporaryFile);

            throw FileException::forCannotMoveFile($temporaryFile, $targetPath);
        }

        return $targetPath;
    }

    private static function normalizeExtension(?string $extension): string
    {
        $extension = trim((string) $extension, ". \t\n\r\0\x0B");

        if ($extension === '' || str_contains($extension, '/') || str_contains($extension, '\\')) {
            return '';
        }

        return $extension;
    }
}
