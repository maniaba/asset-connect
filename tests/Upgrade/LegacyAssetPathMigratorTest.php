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
    public function testMigratesLegacyRelativePathToConfiguredStorage(): void
    {
        $relativePath = 'assets/2026/legacy.txt';
        $this->createStoredFile($relativePath, 'legacy contents');
        $assetId  = $this->insertLegacyAsset($relativePath);
        $progress = [];

        $summary = $this->migrator()->migrate(
            new LegacyAssetPathMigrationOptions(storage: 'public'),
            static function (LegacyAssetPathMigrationProgress $item) use (&$progress): void {
                $progress[] = $item;
            },
        );

        $this->assertSame(1, $summary->total);
        $this->assertSame(1, $summary->migrated);
        $this->assertSame(0, $summary->failed);
        $this->assertCount(1, $progress);
        $this->assertSame(LegacyAssetPathMigrationProgress::STATUS_MIGRATED, $progress[0]->status);
        $this->assertSame($relativePath, $progress[0]->relativePath);
        $this->assertSame('legacy contents', file_get_contents($this->publicStorageRoot . DIRECTORY_SEPARATOR . 'assets/2026/legacy.txt'));

        $this->assertAssetRow($assetId, 'public', $relativePath);
    }

    public function testDryRunReportsMigrationWithoutUpdating(): void
    {
        $relativePath = 'assets/dry-run.txt';
        $this->createStoredFile($relativePath, 'dry run contents');
        $assetId = $this->insertLegacyAsset($relativePath);

        $summary = $this->migrator()->migrate(new LegacyAssetPathMigrationOptions(
            storage: 'public',
            dryRun: true,
        ));

        $this->assertSame(1, $summary->total);
        $this->assertSame(1, $summary->dryRun);
        $this->assertAssetRow($assetId, '', $relativePath);
    }

    public function testReportsFailureWhenTargetFileDoesNotExistOnStorageDisk(): void
    {
        $assetId = $this->insertLegacyAsset('assets/missing.txt');

        $summary = $this->migrator()->migrate(new LegacyAssetPathMigrationOptions(storage: 'public'));

        $this->assertSame(1, $summary->total);
        $this->assertSame(1, $summary->failed);
        $this->assertAssetRow($assetId, '', 'assets/missing.txt');
    }

    public function testCopiesLegacyAbsolutePathToConfiguredStorageUsingStorageMetadata(): void
    {
        $relativePath = 'assets/2026-06-06/154559.1780753559/signature.svg';
        $legacyRoot   = $this->sourceFilesRoot . DIRECTORY_SEPARATOR . 'legacy-writable';

        mkdir($legacyRoot, 0755, true);

        $legacyRoot = realpath($legacyRoot);

        $this->assertIsString($legacyRoot);

        $absolutePath = $this->createFileInRoot($legacyRoot, $relativePath, 'legacy signature');
        $metadata     = [
            'user_custom'    => [],
            'asset_variants' => [],
            'storage_info'   => [
                'storage_base_directory_path' => $legacyRoot . DIRECTORY_SEPARATOR,
                'file_relative_path'          => 'assets/2026-06-06/154559.1780753559/',
            ],
        ];

        $assetId = $this->insertLegacyAsset($absolutePath, $metadata);

        $progress = [];
        $summary  = $this->migrator()->migrate(
            new LegacyAssetPathMigrationOptions(storage: 'public'),
            static function (LegacyAssetPathMigrationProgress $item) use (&$progress): void {
                $progress[] = $item;
            },
        );

        $this->assertSame(1, $summary->total);
        $this->assertCount(1, $progress);
        $this->assertSame($relativePath, $progress[0]->relativePath);
        $this->assertSame('Legacy file copied and database row updated.', $progress[0]->message);
        $this->assertSame(LegacyAssetPathMigrationProgress::STATUS_MIGRATED, $progress[0]->status);
        $this->assertSame(1, $summary->migrated);
        $this->assertSame(0, $summary->failed);
        $this->assertFileExists($absolutePath);
        $this->assertSame('legacy signature', file_get_contents($this->publicStorageRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath)));
        $this->assertAssetRow($assetId, 'public', $relativePath);
    }

    public function testRejectsAbsolutePathWithoutSplittingRoot(): void
    {
        $assetId = $this->insertLegacyAsset('/old/app/public/assets/legacy.txt');

        $summary = $this->migrator()->migrate(new LegacyAssetPathMigrationOptions(storage: 'public'));

        $this->assertSame(1, $summary->total);
        $this->assertSame(1, $summary->failed);
        $this->assertAssetRow($assetId, '', '/old/app/public/assets/legacy.txt');
    }

    public function testUpdatesDatabaseWhenTargetAlreadyExists(): void
    {
        $relativePath = 'assets/already-copied.txt';
        $this->createStoredFile($relativePath, 'existing target contents');

        $assetId = $this->insertLegacyAsset($relativePath);

        $summary = $this->migrator()->migrate(new LegacyAssetPathMigrationOptions(storage: 'public'));

        $this->assertSame(1, $summary->migrated);
        $this->assertSame('existing target contents', file_get_contents($this->publicStorageRoot . DIRECTORY_SEPARATOR . 'assets/already-copied.txt'));
        $this->assertAssetRow($assetId, 'public', $relativePath);
    }

    private function migrator(): LegacyAssetPathMigrator
    {
        return new LegacyAssetPathMigrator($this->assetConfig, $this->db);
    }

    private function createStoredFile(string $relativePath, string $contents): void
    {
        $path = $this->publicStorageRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $dir  = dirname($path);

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, $contents);
    }

    private function createFileInRoot(string $root, string $relativePath, string $contents): string
    {
        $path = rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $dir  = dirname($path);

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, $contents);

        return $path;
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function insertLegacyAsset(string $path, array $metadata = []): int
    {
        $this->db->table($this->tables['assets'])->insert([
            'entity_type' => $this->assetConfig->getEntityTypeKey(FakeAssetEntity::class),
            'entity_id'   => 1,
            'collection'  => 'fake_documents',
            'storage'     => '',
            'name'        => 'Legacy file',
            'file_name'   => basename($path),
            'mime_type'   => 'text/plain',
            'size'        => 15,
            'path'        => $path,
            'order'       => 0,
            'metadata'    => json_encode($metadata),
            'created_at'  => '2026-06-12 10:00:00',
            'updated_at'  => '2026-06-12 10:00:00',
            'deleted_at'  => null,
        ]);

        return (int) $this->db->insertID();
    }

    private function assertAssetRow(int $assetId, string $storage, string $path): void
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
