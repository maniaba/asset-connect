<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Upgrade;

final readonly class LegacyAssetPathMigrationOptions
{
    public function __construct(
        public ?string $storage = null,
        public ?string $fromRoot = null,
        public ?string $sourceRoot = null,
        public bool $dryRun = false,
        public bool $deleteSource = false,
        public bool $overwrite = false,
        public ?int $limit = null,
        public int $batchSize = 100,
    ) {
    }
}
