<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Exceptions;

class FileException extends AssetException
{
    public static function forInvalidFile(string $filePath): static
    {
        $message = lang('Asset.exception.invalid_file', ['path' => $filePath]);

        return new static(
            $message,
            'Invalid file provided',
            400,
        );
    }

    public static function forFileNotFound(string $sourcePath): static
    {
        $message = lang('Asset.exception.file_not_found', ['path' => $sourcePath]);

        return new static(
            $message,
            'File not found',
            404,
        );
    }

    public static function forCannotCopyFile(string $sourcePath, string $fullPath): static
    {
        $message = lang('Asset.exception.cannot_copy_file', ['source' => $sourcePath, 'destination' => $fullPath]);

        return new static(
            $message,
            'Cannot copy file',
            500,
        );
    }

    public static function forCannotMoveFile(string $sourcePath, string $storePath): static
    {
        $message = lang('Asset.exception.cannot_move_file', ['source' => $sourcePath, 'destination' => $storePath]);

        return new static(
            $message,
            'Cannot move file',
            500,
        );
    }
}
