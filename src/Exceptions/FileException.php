<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Exceptions;

final class FileException extends AssetException
{
    public static function forInvalidFile(string $filePath): self
    {
        $message = lang('Asset.exception.invalid_file', ['path' => $filePath]);

        return new self(
            $message,
            'Invalid file provided',
            400,
        );
    }

    public static function forFileNotFound(string $sourcePath): self
    {
        $message = lang('Asset.exception.file_not_found', ['path' => $sourcePath]);

        return new self(
            $message,
            'File not found',
            404,
        );
    }

    public static function forCannotCopyFile(string $sourcePath, string $fullPath): self
    {
        $message = lang('Asset.exception.cannot_copy_file', ['source' => $sourcePath, 'destination' => $fullPath]);

        return new self(
            $message,
            'Cannot copy file',
            500,
        );
    }

    public static function forCannotMoveFile(string $sourcePath, string $storePath): self
    {
        $message = lang('Asset.exception.cannot_move_file', ['source' => $sourcePath, 'destination' => $storePath]);

        return new self(
            $message,
            'Cannot move file',
            500,
        );
    }
}
