<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Storage;

final readonly class StorageLinkResult
{
    public function __construct(
        public string $storage,
        public StorageLinkStatus $status,
        public string $source,
        public string $target,
        public string $message,
    ) {
    }
}
