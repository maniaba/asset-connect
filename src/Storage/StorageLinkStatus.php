<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Storage;

enum StorageLinkStatus: string
{
    case LINKED   = 'linked';
    case EXISTING = 'existing';
    case SKIPPED  = 'skipped';
    case FAILED   = 'failed';
}
