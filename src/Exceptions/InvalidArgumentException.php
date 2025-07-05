<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Exceptions;

use Throwable;

final class InvalidArgumentException extends AssetException
{
    public function __construct(array|string $errors, string $message = 'Invalid argument provided', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($errors, $message, $code, $previous);
    }
}
