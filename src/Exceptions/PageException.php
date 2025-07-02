<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Exceptions;

use CodeIgniter\Exceptions\PageNotFoundException;

final class PageException extends PageNotFoundException
{
    public static function forForbiddenAccess(?string $message = null): self
    {
        $message ??= lang('Auth.exceptions.page_forbidden');

        return new self($message, 403);
    }

    public static function forVariantNotFound(string $variantName): self
    {
        return new self(lang('Auth.exceptions.variant_not_found', [
            'variantName' => $variantName,
        ]), 404);
    }

    public static function forFileNotFound(string $filePath): self
    {
        return new self(lang('Auth.exceptions.file_not_found', [
            'path' => $filePath,
        ]), 404);
    }
}
