<?php

declare(strict_types=1);

namespace Tests\Upgrade;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\BaseResult;
use Maniaba\AssetConnect\Enums\AssetVisibility;
use Maniaba\AssetConnect\Storage\Interfaces\StorageDiskInterface;
use Maniaba\AssetConnect\Upgrade\LegacyAssetPathMigrationOptions;
use Maniaba\AssetConnect\Upgrade\LegacyAssetPathMigrationProgress;
use Maniaba\AssetConnect\Upgrade\LegacyAssetPathMigrator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
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

    public function testSkipsLegacyRowsWithEmptyPath(): void
    {
        $assetId = $this->insertLegacyAsset('');

        $summary = $this->migrator()->migrate(new LegacyAssetPathMigrationOptions(storage: 'public'));

        $this->assertSame(1, $summary->total);
        $this->assertSame(1, $summary->skipped);
        $this->assertAssetRow($assetId, '', '');
    }

    public function testLimitCapsCandidateCountAndLeavesLaterRowsUntouched(): void
    {
        $firstPath  = 'assets/limit-first.txt';
        $secondPath = 'assets/limit-second.txt';

        $this->createStoredFile($firstPath, 'first');
        $this->createStoredFile($secondPath, 'second');

        $firstAssetId  = $this->insertLegacyAsset($firstPath);
        $secondAssetId = $this->insertLegacyAsset($secondPath);

        $summary = $this->migrator()->migrate(new LegacyAssetPathMigrationOptions(
            storage: 'public',
            limit: 1,
            batchSize: 1,
        ));

        $this->assertSame(1, $summary->total);
        $this->assertSame(1, $summary->migrated);
        $this->assertAssetRow($firstAssetId, 'public', $firstPath);
        $this->assertAssetRow($secondAssetId, '', $secondPath);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testMigrateStopsWhenCountedCandidateBatchDisappears(): void
    {
        $countBuilder   = $this->queryBuilder();
        $batchBuilder   = $this->queryBuilder();
        $cleanupBuilder = $this->queryBuilder();

        $countBuilder->method('countAllResults')->willReturn(1);
        $batchBuilder->method('get')->willReturn($this->resultRows([]));
        $cleanupBuilder->method('get')->willReturn($this->resultRows([]));

        $migrator = new LegacyAssetPathMigrator(
            $this->assetConfig,
            $this->connectionReturning($countBuilder, $batchBuilder, $cleanupBuilder),
        );

        $summary = $migrator->migrate(new LegacyAssetPathMigrationOptions(storage: 'public'));

        $this->assertSame(1, $summary->total);
        $this->assertSame(0, $summary->migrated);
        $this->assertSame(0, $summary->failed);
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

        $metadata = $this->assetMetadata($assetId);

        $this->assertIsArray($metadata);
        $this->assertArrayNotHasKey('storage_info', $metadata);
        $this->assertSame([], $metadata['user_custom']);
        $this->assertSame([], $metadata['asset_variants']);
    }

    public function testReportsFailureWhenLegacyFileCopyToStorageFails(): void
    {
        $relativePath = 'assets/copy-fails.txt';
        $legacyRoot   = $this->sourceFilesRoot . DIRECTORY_SEPARATOR . 'legacy-copy-failure';

        mkdir($legacyRoot, 0755, true);

        $legacyRoot = realpath($legacyRoot);

        $this->assertIsString($legacyRoot);

        $absolutePath = $this->createFileInRoot($legacyRoot, $relativePath, 'legacy copy failure');
        $metadata     = [
            'storage_info' => [
                'storage_base_directory_path' => $legacyRoot . DIRECTORY_SEPARATOR,
                'file_relative_path'          => 'assets/',
            ],
        ];

        $disk = $this->createMock(StorageDiskInterface::class);
        $disk->method('name')->willReturn('public');
        $disk->method('visibility')->willReturn(AssetVisibility::PUBLIC);
        $disk->expects($this->once())
            ->method('fileExists')
            ->with($relativePath)
            ->willReturn(false);
        $disk->expects($this->once())
            ->method('writeStream')
            ->with($relativePath)
            ->willThrowException(new RuntimeException('adapter refused copy'));

        $this->assetConfig->storages['public'] = [
            'disk' => $disk,
        ];

        $assetId = $this->insertLegacyAsset($absolutePath, $metadata);

        $summary = $this->migrator()->migrate(new LegacyAssetPathMigrationOptions(storage: 'public'));

        $this->assertSame(1, $summary->failed);
        $this->assertAssetRow($assetId, '', $absolutePath);
    }

    public function testCleansLegacyStorageInfoFromAlreadyMigratedRows(): void
    {
        $expectedMetadata = [
            'user_custom' => [
                'caption' => 'Migrated row',
                'flags'   => [
                    'featured' => true,
                    'private'  => false,
                ],
            ],
            'asset_variants' => [
                'thumbnail' => [
                    'name'      => 'thumbnail',
                    'file_name' => 'thumb.jpg',
                    'storage'   => 'public',
                    'path'      => 'assets/already-migrated/thumb.jpg',
                ],
            ],
            'checksum'    => 'sha256:123456',
            'sort_order'  => 10,
            'reviewed_at' => null,
        ];
        $metadata = [
            ...$expectedMetadata,
            'storage_info' => [
                'storage'            => 'public',
                'file_relative_path' => 'assets/already-migrated/',
            ],
        ];
        $assetId = $this->insertLegacyAsset('assets/already-migrated/file.txt', $metadata, 'public');

        $summary = $this->migrator()->migrate(new LegacyAssetPathMigrationOptions(storage: 'public'));

        $this->assertSame(0, $summary->total);
        $this->assertSame(0, $summary->migrated);
        $this->assertSame(1, $summary->metadataCleaned);
        $this->assertSame(0, $summary->metadataFailed);
        $this->assertAssetRow($assetId, 'public', 'assets/already-migrated/file.txt');

        $cleanedMetadata = $this->assetMetadata($assetId);

        $this->assertIsArray($cleanedMetadata);
        $this->assertArrayNotHasKey('storage_info', $cleanedMetadata);
        $this->assertSame($expectedMetadata, $cleanedMetadata);
    }

    public function testCleansStorageOnlyLegacyMetadataToNull(): void
    {
        $metadata = [
            'storage_info' => [
                'storage'            => 'public',
                'file_relative_path' => 'assets/storage-only/',
            ],
        ];
        $assetId = $this->insertLegacyAsset('assets/storage-only/file.txt', $metadata, 'public');

        $summary = $this->migrator()->migrate(new LegacyAssetPathMigrationOptions(storage: 'public'));

        $this->assertSame(1, $summary->metadataCleaned);
        $this->assertNull($this->assetMetadata($assetId), 'Removing the only metadata key should store null metadata.');
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testMetadataCleanupRecordsUpdateFailure(): void
    {
        $countBuilder   = $this->queryBuilder();
        $cleanupBuilder = $this->queryBuilder();
        $updateBuilder  = $this->queryBuilder();

        $countBuilder->method('countAllResults')->willReturn(0);
        $cleanupBuilder->method('get')->willReturn($this->resultRows([
            [
                'id'       => 10,
                'storage'  => 'public',
                'path'     => 'assets/cleanup-fails.txt',
                'metadata' => '{"storage_info":{"storage":"public"}}',
            ],
        ]));
        $updateBuilder->method('update')->willReturn(false);

        $migrator = new LegacyAssetPathMigrator(
            $this->assetConfig,
            $this->connectionReturning($countBuilder, $cleanupBuilder, $updateBuilder),
        );

        $summary = $migrator->migrate(new LegacyAssetPathMigrationOptions(
            storage: 'public',
            limit: 1,
        ));

        $this->assertSame(0, $summary->total);
        $this->assertSame(1, $summary->metadataFailed);
        $this->assertSame(0, $summary->metadataCleaned);
    }

    public function testDryRunReportsLegacyStorageInfoCleanupWithoutUpdatingMetadata(): void
    {
        $metadata = [
            'storage_info' => [
                'storage'            => 'public',
                'file_relative_path' => 'assets/dry-run-cleanup/',
            ],
        ];
        $assetId = $this->insertLegacyAsset('assets/dry-run-cleanup/file.txt', $metadata, 'public');

        $summary = $this->migrator()->migrate(new LegacyAssetPathMigrationOptions(
            storage: 'public',
            dryRun: true,
        ));

        $this->assertSame(0, $summary->total);
        $this->assertSame(1, $summary->metadataDryRun);
        $this->assertSame(0, $summary->metadataCleaned);

        $metadataAfterDryRun = $this->assetMetadata($assetId);

        $this->assertIsArray($metadataAfterDryRun);
        $this->assertArrayHasKey('storage_info', $metadataAfterDryRun);
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

    public function testMigratesUsingCollectionStorageWhenNoStorageOptionIsProvided(): void
    {
        $relativePath = 'assets/collection-storage.txt';
        $this->createStoredFile($relativePath, 'collection storage');
        $assetId = $this->insertLegacyAsset($relativePath);

        $summary = $this->migrator()->migrate(new LegacyAssetPathMigrationOptions());

        $this->assertSame(1, $summary->migrated);
        $this->assertAssetRow($assetId, 'public', $relativePath);
    }

    public function testMigratesUsingDefaultPublicStorageWhenCollectionCannotBeResolved(): void
    {
        $relativePath = 'assets/default-public-storage.txt';
        $this->createStoredFile($relativePath, 'default public storage');
        $assetId = $this->insertLegacyAsset($relativePath, collection: 'unknown_collection');

        $summary = $this->migrator()->migrate(new LegacyAssetPathMigrationOptions());

        $this->assertSame(1, $summary->migrated);
        $this->assertAssetRow($assetId, 'public', $relativePath);
    }

    public function testPrivateStorageAndPathHelpersCoverLegacyEdgeCases(): void
    {
        $migrator = $this->migrator();

        $resolveStorageName = $this->getPrivateMethodInvoker($migrator, 'resolveStorageName');
        $this->assertSame(
            'configured',
            $resolveStorageName(['storage' => 'row-storage'], new LegacyAssetPathMigrationOptions(storage: 'configured')),
            'Explicit migration storage option should win over row storage.',
        );
        $this->assertSame(
            'row-storage',
            $resolveStorageName(['storage' => ' row-storage '], new LegacyAssetPathMigrationOptions()),
            'Existing row storage should be used when no option is configured.',
        );

        $normalizeRelativePath = $this->getPrivateMethodInvoker($migrator, 'normalizeRelativePath');
        $this->assertSame('assets/file.txt', $normalizeRelativePath('assets\\file.txt'));
        $this->assertNull($normalizeRelativePath(''));
        $this->assertNull($normalizeRelativePath('/absolute/file.txt'));
        $this->assertNull($normalizeRelativePath('C:\\absolute\\file.txt'));
        $this->assertNull($normalizeRelativePath('../escape.txt'));
        $this->assertNull($normalizeRelativePath('assets/../escape.txt'));

        $normalizeRelativeDirectoryPath = $this->getPrivateMethodInvoker($migrator, 'normalizeRelativeDirectoryPath');
        $this->assertSame('assets/nested/', $normalizeRelativeDirectoryPath('assets/nested'));
        $this->assertNull($normalizeRelativeDirectoryPath('/absolute'));

        $hasCleanableStorageInfo = $this->getPrivateMethodInvoker($migrator, 'hasCleanableStorageInfo');
        $this->assertFalse($hasCleanableStorageInfo(['storage' => '', 'path' => 'assets/file.txt', 'metadata' => '{"storage_info":[]}']));
        $this->assertFalse($hasCleanableStorageInfo(['storage' => 'public', 'path' => '/absolute/file.txt', 'metadata' => '{"storage_info":[]}']));
        $this->assertFalse($hasCleanableStorageInfo(['storage' => 'public', 'path' => 'assets/file.txt', 'metadata' => '']));
        $this->assertFalse($hasCleanableStorageInfo(['storage' => 'public', 'path' => 'assets/file.txt', 'metadata' => '{invalid-json']));
        $this->assertFalse($hasCleanableStorageInfo(['storage' => 'public', 'path' => 'assets/file.txt', 'metadata' => '{"caption":"test"}']));

        $removeStorageInfoFromMetadata = $this->getPrivateMethodInvoker($migrator, 'removeStorageInfoFromMetadata');
        $this->assertSame('{invalid-json', $removeStorageInfoFromMetadata('{invalid-json'));

        $resolveRelativePathFromLegacyMetadata = $this->getPrivateMethodInvoker($migrator, 'resolveRelativePathFromLegacyMetadata');
        $this->assertNull($resolveRelativePathFromLegacyMetadata('/legacy/assets/file.txt', ''));
        $this->assertNull($resolveRelativePathFromLegacyMetadata('/legacy/assets/file.txt', '{invalid-json'));
        $this->assertNull($resolveRelativePathFromLegacyMetadata('/legacy/assets/file.txt', '{"caption":"test"}'));
        $this->assertNull($resolveRelativePathFromLegacyMetadata('/legacy/assets/file.txt', '{"storage_info":[]}'));
        $this->assertNull($resolveRelativePathFromLegacyMetadata('/legacy/assets/file.txt', '{"storage_info":{"storage_base_directory_path":""}}'));
        $this->assertNull($resolveRelativePathFromLegacyMetadata('', '{"storage_info":{"storage_base_directory_path":"/legacy"}}'));
        $this->assertNull($resolveRelativePathFromLegacyMetadata('/legacy/assets/file.txt', '{"storage_info":{"storage_base_directory_path":"/other/root"}}'));
        $this->assertNull($resolveRelativePathFromLegacyMetadata('/legacy/', '{"storage_info":{"storage_base_directory_path":"/legacy"}}'));
        $this->assertNull($resolveRelativePathFromLegacyMetadata(
            '/legacy/assets/file.txt',
            '{"storage_info":{"storage_base_directory_path":"/legacy","file_relative_path":"other/"}}',
        ));
        $this->assertNull($resolveRelativePathFromLegacyMetadata(
            '/legacy/assets/file.txt',
            '{"storage_info":{"storage_base_directory_path":"/legacy","file_relative_path":"/absolute"}}',
        ));

        $copyLegacySourceToStorage = $this->getPrivateMethodInvoker($migrator, 'copyLegacySourceToStorage');
        $disk                      = $this->createStub(StorageDiskInterface::class);
        $previousErrorReporting    = error_reporting();

        error_reporting($previousErrorReporting & ~E_WARNING);

        try {
            $this->assertFalse($copyLegacySourceToStorage($this->sourceFilesRoot . DIRECTORY_SEPARATOR . 'missing-copy-source.txt', $disk, 'assets/file.txt'));
        } finally {
            error_reporting($previousErrorReporting);
        }
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testMigrateRowReportsDatabaseUpdateFailure(): void
    {
        $relativePath = 'assets/update-fails.txt';

        $disk = $this->createMock(StorageDiskInterface::class);
        $disk->method('name')->willReturn('public');
        $disk->method('visibility')->willReturn(AssetVisibility::PUBLIC);
        $disk->expects($this->once())
            ->method('fileExists')
            ->with($relativePath)
            ->willReturn(true);

        $this->assetConfig->storages['public'] = [
            'disk' => $disk,
        ];

        $updateBuilder = $this->queryBuilder();
        $updateBuilder->method('update')->willReturn(false);

        $migrator   = new LegacyAssetPathMigrator($this->assetConfig, $this->connectionReturning($updateBuilder));
        $migrateRow = $this->getPrivateMethodInvoker($migrator, 'migrateRow');

        $progress = $migrateRow([
            'id'         => 99,
            'collection' => 'fake_documents',
            'storage'    => '',
            'path'       => $relativePath,
            'metadata'   => '',
        ], new LegacyAssetPathMigrationOptions(storage: 'public'), 1, 1);

        $this->assertSame(LegacyAssetPathMigrationProgress::STATUS_FAILED, $progress->status);
        $this->assertSame('Database row could not be updated.', $progress->message);
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
    private function insertLegacyAsset(
        string $path,
        array $metadata = [],
        string $storage = '',
        string $collection = 'fake_documents',
    ): int {
        $this->db->table($this->tables['assets'])->insert([
            'entity_type' => $this->assetConfig->getEntityTypeKey(FakeAssetEntity::class),
            'entity_id'   => 1,
            'collection'  => $collection,
            'storage'     => $storage,
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

    /**
     * @return array<string, mixed>|null
     */
    private function assetMetadata(int $assetId): ?array
    {
        $row = $this->db->table($this->tables['assets'])
            ->select('metadata')
            ->where('id', $assetId)
            ->get()
            ->getRowArray();

        $metadata = is_array($row) ? $row['metadata'] : null;

        if (! is_string($metadata) || $metadata === '') {
            return null;
        }

        $decoded = json_decode($metadata, true);

        return is_array($decoded) ? $decoded : null;
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

    private function queryBuilder(): BaseBuilder&MockObject
    {
        $builder = $this->createMock(BaseBuilder::class);

        foreach (['select', 'groupStart', 'where', 'orWhere', 'groupEnd', 'orderBy', 'limit'] as $method) {
            $builder->method($method)->willReturnSelf();
        }

        return $builder;
    }

    private function connectionReturning(BaseBuilder ...$builders): BaseConnection
    {
        $connection = $this->createMock(BaseConnection::class);
        $index      = 0;

        $connection->method('table')
            ->willReturnCallback(static function () use ($builders, &$index): BaseBuilder {
                return $builders[$index++] ?? throw new RuntimeException('Unexpected table call.');
            });

        return $connection;
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function resultRows(array $rows): BaseResult
    {
        $result = $this->createMock(BaseResult::class);

        $result->method('getResultArray')->willReturn($rows);

        return $result;
    }
}
