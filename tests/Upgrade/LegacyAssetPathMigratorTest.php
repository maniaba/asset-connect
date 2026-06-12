<?php

declare(strict_types=1);

namespace Tests\Upgrade;

use Maniaba\AssetConnect\Upgrade\LegacyAssetPathMigrationOptions;
use Maniaba\AssetConnect\Upgrade\LegacyAssetPathMigrationProgress;
use Maniaba\AssetConnect\Upgrade\LegacyAssetPathMigrator;
use Tests\Support\AssetConnectFeatureTestCase;
use Tests\Support\Entities\FakeAssetEntity;

/**
 * @internal
 */
final class LegacyAssetPathMigratorTest extends AssetConnectFeatureTestCase
{
    public function testMigratesLegacyAbsolutePathToConfiguredStorage(): void
    {
        $legacyRoot = $this->sourceFilesRoot . DIRECTORY_SEPARATOR . 'legacy-public';
        $legacyFile = $this->createLegacyFile($legacyRoot, 'assets/2026/legacy.txt', 'legacy contents');
        $legacyRoot = (string) realpath($legacyRoot);
        $assetId    = $this->insertLegacyAsset($legacyFile);
        $progress   = [];

        $summary = $this->migrator()->migrate(
            new LegacyAssetPathMigrationOptions(
                storage: 'public',
                fromRoot: $legacyRoot,
            ),
            static function (LegacyAssetPathMigrationProgress $item) use (&$progress): void {
                $progress[] = $item;
            },
        );

        $this->assertSame(1, $summary->total);
        $this->assertSame(1, $summary->migrated);
        $this->assertSame(0, $summary->failed);
        $this->assertCount(1, $progress);
        $this->assertSame(LegacyAssetPathMigrationProgress::STATUS_MIGRATED, $progress[0]->status);
        $this->assertSame('assets/2026/legacy.txt', $progress[0]->relativePath);
        $this->assertFileExists($legacyFile);
        $this->assertSame('legacy contents', file_get_contents($this->publicStorageRoot . DIRECTORY_SEPARATOR . 'assets/2026/legacy.txt'));

        $this->assertAssetRow($assetId, 'public', 'assets/2026/legacy.txt');
    }

    public function testMigratesPathWhenStoredRootDiffersFromCurrentSourceRoot(): void
    {
        $storedRoot = '/old/app/public';
        $sourceRoot = $this->sourceFilesRoot . DIRECTORY_SEPARATOR . 'current-public';
        $this->createLegacyFile($sourceRoot, 'uploads/remapped.txt', 'remapped contents');
        $sourceRoot = (string) realpath($sourceRoot);
        $assetId    = $this->insertLegacyAsset($storedRoot . '/uploads/remapped.txt');

        $summary = $this->migrator()->migrate(new LegacyAssetPathMigrationOptions(
            storage: 'public',
            fromRoot: $storedRoot,
            sourceRoot: $sourceRoot,
        ));

        $this->assertSame(1, $summary->migrated);
        $this->assertSame('remapped contents', file_get_contents($this->publicStorageRoot . DIRECTORY_SEPARATOR . 'uploads/remapped.txt'));
        $this->assertAssetRow($assetId, 'public', 'uploads/remapped.txt');
    }

    public function testDryRunReportsMigrationWithoutCopyingOrUpdating(): void
    {
        $legacyRoot = $this->sourceFilesRoot . DIRECTORY_SEPARATOR . 'dry-run-public';
        $legacyFile = $this->createLegacyFile($legacyRoot, 'assets/dry-run.txt', 'dry run contents');
        $legacyRoot = (string) realpath($legacyRoot);
        $assetId    = $this->insertLegacyAsset($legacyFile);

        $summary = $this->migrator()->migrate(new LegacyAssetPathMigrationOptions(
            storage: 'public',
            fromRoot: $legacyRoot,
            dryRun: true,
        ));

        $this->assertSame(1, $summary->total);
        $this->assertSame(1, $summary->dryRun);
        $this->assertFileDoesNotExist($this->publicStorageRoot . DIRECTORY_SEPARATOR . 'assets/dry-run.txt');
        $this->assertAssetRow($assetId, null, $legacyFile);
    }

    public function testReportsFailureWhenSourceAndTargetDoNotExist(): void
    {
        $assetId = $this->insertLegacyAsset('/missing/app/public/assets/missing.txt');

        $summary = $this->migrator()->migrate(new LegacyAssetPathMigrationOptions(
            storage: 'public',
            fromRoot: '/missing/app/public',
        ));

        $this->assertSame(1, $summary->total);
        $this->assertSame(1, $summary->failed);
        $this->assertAssetRow($assetId, null, '/missing/app/public/assets/missing.txt');
    }

    public function testDeletesSourceAfterSuccessfulMigrationWhenRequested(): void
    {
        $legacyRoot = $this->sourceFilesRoot . DIRECTORY_SEPARATOR . 'cleanup-public';
        $legacyFile = $this->createLegacyFile($legacyRoot, 'assets/delete-me.txt', 'delete source contents');
        $legacyRoot = (string) realpath($legacyRoot);
        $assetId    = $this->insertLegacyAsset($legacyFile);

        $summary = $this->migrator()->migrate(new LegacyAssetPathMigrationOptions(
            storage: 'public',
            fromRoot: $legacyRoot,
            deleteSource: true,
        ));

        $this->assertSame(1, $summary->migrated);
        $this->assertFileDoesNotExist($legacyFile);
        $this->assertSame('delete source contents', file_get_contents($this->publicStorageRoot . DIRECTORY_SEPARATOR . 'assets/delete-me.txt'));
        $this->assertAssetRow($assetId, 'public', 'assets/delete-me.txt');
    }

    public function testUpdatesDatabaseWhenTargetAlreadyExistsWithoutSource(): void
    {
        $relativePath = 'assets/already-copied.txt';
        $targetPath   = $this->publicStorageRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $targetDir    = dirname($targetPath);

        if (! is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        file_put_contents($targetPath, 'existing target contents');

        $assetId = $this->insertLegacyAsset('/old/app/public/' . $relativePath);

        $summary = $this->migrator()->migrate(new LegacyAssetPathMigrationOptions(
            storage: 'public',
            fromRoot: '/old/app/public',
        ));

        $this->assertSame(1, $summary->migrated);
        $this->assertSame('existing target contents', file_get_contents($targetPath));
        $this->assertAssetRow($assetId, 'public', $relativePath);
    }

    private function migrator(): LegacyAssetPathMigrator
    {
        return new LegacyAssetPathMigrator($this->assetConfig, $this->db);
    }

    private function createLegacyFile(string $root, string $relativePath, string $contents): string
    {
        $path = rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $dir  = dirname($path);

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, $contents);

        return (string) realpath($path);
    }

    private function insertLegacyAsset(string $path): int
    {
        $this->db->table($this->tables['assets'])->insert([
            'entity_type' => $this->assetConfig->getEntityTypeKey(FakeAssetEntity::class),
            'entity_id'   => 1,
            'collection'  => 'fake_documents',
            'storage'     => null,
            'name'        => 'Legacy file',
            'file_name'   => basename($path),
            'mime_type'   => 'text/plain',
            'size'        => 15,
            'path'        => $path,
            'order'       => 0,
            'metadata'    => json_encode([]),
            'created_at'  => '2026-06-12 10:00:00',
            'updated_at'  => '2026-06-12 10:00:00',
            'deleted_at'  => null,
        ]);

        return (int) $this->db->insertID();
    }

    private function assertAssetRow(int $assetId, ?string $storage, string $path): void
    {
        $row = $this->db->table($this->tables['assets'])
            ->where('id', $assetId)
            ->get()
            ->getRowArray();

        $this->assertIsArray($row);
        $this->assertSame($storage, $row['storage']);
        $this->assertSame($path, $row['path']);
    }
}
