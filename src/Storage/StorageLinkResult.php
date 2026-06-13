<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Storage;

final readonly class StorageLinkResult
{
    public const string STATUS_LINKED   = 'linked';
    public const string STATUS_EXISTING = 'existing';
    public const string STATUS_SKIPPED  = 'skipped';
    public const string STATUS_FAILED   = 'failed';

    public function __construct(
        public string $storage,
        public string $status,
        public string $source,
        public string $target,
        public string $message,
    ) {
    }
}
