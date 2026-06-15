<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Upgrade;

final readonly class LegacyAssetPathMigrationProgress
{
    public const string STATUS_MIGRATED = 'migrated';
    public const string STATUS_DRY_RUN  = 'dry-run';
    public const string STATUS_SKIPPED  = 'skipped';
    public const string STATUS_FAILED   = 'failed';

    public function __construct(
        public int $current,
        public int $total,
        public int $assetId,
        public string $status,
        public string $message,
        public string $storage = '',
        public string $relativePath = '',
    ) {
    }
}
