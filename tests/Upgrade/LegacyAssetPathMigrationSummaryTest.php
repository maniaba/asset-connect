<?php

declare(strict_types=1);

namespace Tests\Upgrade;

use CodeIgniter\Test\CIUnitTestCase;
use Maniaba\AssetConnect\Upgrade\LegacyAssetPathMigrationProgress;
use Maniaba\AssetConnect\Upgrade\LegacyAssetPathMigrationSummary;

/**
 * @internal
 */
final class LegacyAssetPathMigrationSummaryTest extends CIUnitTestCase
{
    public function testRecordCountsKnownStatusesAndIgnoresUnknownStatus(): void
    {
        $summary = new LegacyAssetPathMigrationSummary();

        $summary->record($this->progress(LegacyAssetPathMigrationProgress::STATUS_MIGRATED));
        $summary->record($this->progress(LegacyAssetPathMigrationProgress::STATUS_DRY_RUN));
        $summary->record($this->progress(LegacyAssetPathMigrationProgress::STATUS_SKIPPED));
        $summary->record($this->progress(LegacyAssetPathMigrationProgress::STATUS_FAILED));
        $summary->record($this->progress('unknown'));

        $this->assertSame(1, $summary->migrated, 'Migrated progress should increment migrated count.');
        $this->assertSame(1, $summary->dryRun, 'Dry-run progress should increment dry-run count.');
        $this->assertSame(1, $summary->skipped, 'Skipped progress should increment skipped count.');
        $this->assertSame(1, $summary->failed, 'Failed progress should increment failed count.');
    }

    private function progress(string $status): LegacyAssetPathMigrationProgress
    {
        return new LegacyAssetPathMigrationProgress(1, 1, 1, $status, 'message');
    }
}
