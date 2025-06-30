<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Exceptions;

use RuntimeException;

class AuthorizationException extends RuntimeException
{
    // This exception is thrown when an entity is not authorized to access an asset
}
