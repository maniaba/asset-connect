<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Exceptions;

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

    public static function forInvalidEntity(Entity $entity): self
    {
        $message = lang('Asset.exception.invalid_entity', ['entity' => $entity::class]);

        return new self($message, $message, 400);
    }

    public static function forFileNameNotAllowed(string $fileName): self
    {
        $message = lang('Asset.exception.file_name_not_allowed', ['fileName' => $fileName]);

        return new self($message, 'File name not allowed', 400);
    }

    public static function forFileTooLarge(int $fileSize, int $maxFileSize): self
    {
        $message = lang('Asset.exception.file_too_large', [
            'fileSize'    => $fileSize,
            'maxFileSize' => $maxFileSize,
        ]);

        return new self($message, 'File size exceeds the maximum allowed size', 413);
    }

    public static function forInvalidFileExtension(string $extension, array $allowedExtensions): self
    {
        $message = lang('Asset.exception.invalid_file_extension', [
            'extension'         => $extension,
            'allowedExtensions' => implode(', ', $allowedExtensions),
        ]);

        return new self($message, 'Invalid file extension', 400);
    }

    public static function forInvalidMimeType(string $mimeType, array $allowedMimeTypes): self
    {
        $message = lang('Asset.exception.invalid_mime_type', [
            'mimeType'         => $mimeType,
            'allowedMimeTypes' => implode(', ', $allowedMimeTypes),
        ]);

        return new self($message, 'Invalid MIME type', 400);
    }

    public static function forDatabaseError(array $errors): self
    {
        $message = lang('Asset.exception.database_error', ['errors' => implode(', ', $errors)]);

        return new self($errors, $message, 500);
    }

    public static function forPendingAssetNotFound(string $id): self
    {
        $message = lang('Asset.exception.pending_asset_not_found', ['id' => $id]);

        return new self($message, $message, 404);
    }

    public static function forMissingEntityKeyDefinition(string $entityClass): self
    {
        $message = lang('Asset.exception.missing_entity_key_definition', ['entityClass' => $entityClass]);

        return new self($message, $message, 500);
    }
}
