<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Upgrade;

final readonly class LegacyAssetPathMigrationOptions
{
    public function __construct(
        public ?string $storage = null,
        public bool $dryRun = false,
        public ?int $limit = null,
        public int $batchSize = 100,
    ) {
    }
}
