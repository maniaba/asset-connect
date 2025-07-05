<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Enums;

enum AssetVisibility: string
{
    case PUBLIC    = 'public';
    case PROTECTED = 'protected';
}
