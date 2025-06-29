<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Exceptions;

use CodeIgniter\Entity\Entity;
use LogicException;
use Throwable;

class AssetException extends LogicException
{
    protected $code = 500;
    public readonly array $errors;

    public function __construct(
        array|string $errors,
        string $message = 'An error occurred while processing the asset',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        $this->errors = is_string($errors) ? [$errors] : $errors;

        parent::__construct($message, $code, $previous);
    }

    public static function forInvalidEntity(Entity $entity): static
    {
        $message = lang('Asset.exception.invalid_entity', ['entity' => $entity::class]);

        return new static($message, $message, 400);
    }

    public static function forFileNameNotAllowed(string $fileName): static
    {
        $message = lang('Asset.exception.file_name_not_allowed', ['fileName' => $fileName]);

        return new static($message, 'File name not allowed', 400);
    }

    public static function forFileTooLarge(int $fileSize, int $maxFileSize): static
    {
        $message = lang('Asset.exception.file_too_large', [
            'fileSize'    => $fileSize,
            'maxFileSize' => $maxFileSize,
        ]);

        return new static($message, 'File size exceeds the maximum allowed size', 413);
    }

    public static function forInvalidFileExtension(string $extension, array $allowedExtensions): static
    {
        $message = lang('Asset.exception.invalid_file_extension', [
            'extension'         => $extension,
            'allowedExtensions' => implode(', ', $allowedExtensions),
        ]);

        return new static($message, 'Invalid file extension', 400);
    }

    public static function forInvalidMimeType(string $mimeType, array $allowedMimeTypes): static
    {
        $message = lang('Asset.exception.invalid_mime_type', [
            'mimeType'         => $mimeType,
            'allowedMimeTypes' => implode(', ', $allowedMimeTypes),
        ]);

        return new static($message, 'Invalid MIME type', 400);
    }

    public static function forDatabaseError(array $errors): static
    {
        $message = lang('Asset.exception.database_error', ['errors' => implode(', ', $errors)]);

        return new static($errors, $message, 500);
    }
}
