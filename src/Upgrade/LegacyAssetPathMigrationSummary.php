<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Upgrade;

final class LegacyAssetPathMigrationSummary
{
    public int $total    = 0;
    public int $migrated = 0;
    public int $dryRun   = 0;
    public int $skipped  = 0;
    public int $failed   = 0;

    public function record(LegacyAssetPathMigrationProgress $progress): void
    {
        match ($progress->status) {
            LegacyAssetPathMigrationProgress::STATUS_MIGRATED => $this->migrated++,
            LegacyAssetPathMigrationProgress::STATUS_DRY_RUN  => $this->dryRun++,
            LegacyAssetPathMigrationProgress::STATUS_SKIPPED  => $this->skipped++,
            LegacyAssetPathMigrationProgress::STATUS_FAILED   => $this->failed++,
            default                                           => null,
        };
    }
}
