<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Enums;

enum AssetVisibility: string
{
    case PUBLIC    = 'public';
    case PROTECTED = 'protected';
}
