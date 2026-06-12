<?php

declare(strict_types=1);

namespace Tests\Database\Migrations;

use Tests\Support\DatabaseTestCase;

/**
 * @internal
 */
final class AssetMigrationsDatabaseTest extends DatabaseTestCase
{
    public function testAssetsTableIncludesStorageColumnAndIndexes(): void
    {
        $table = $this->tables['assets'];

        $this->assertTrue($this->db->tableExists($table));
        $this->assertTrue($this->db->fieldExists('storage', $table));

        $indexes = $this->db->getIndexData($table);

        $this->assertArrayHasKey('assets_storage_index', $indexes);
        $this->assertArrayHasKey('assets_storage_path_index', $indexes);
        $this->assertSame(['storage'], $indexes['assets_storage_index']->fields);
        $this->assertSame(['storage', 'path'], $indexes['assets_storage_path_index']->fields);
    }

    public function testAssetsTableAllowsLegacyRowsWithoutStorage(): void
    {
        $table = $this->tables['assets'];

        $this->insertAssetRow($table, [
            'storage' => null,
            'path'    => '/legacy/full/path.jpg',
        ]);

        $row = $this->db->table($table)
            ->where('path', '/legacy/full/path.jpg')
            ->get()
            ->getRowArray();

        $this->assertIsArray($row);
        $this->assertNull($row['storage']);
    }

    public function testAssetsTableStoresDiskNameAndRelativePath(): void
    {
        $table = $this->tables['assets'];

        $this->insertAssetRow($table, [
            'storage' => 'public',
            'path'    => 'avatars/user-123/profile.jpg',
        ]);

        $this->seeInDatabase($table, [
            'storage' => 'public',
            'path'    => 'avatars/user-123/profile.jpg',
        ]);
    }

    public function testAssetMigrationsCanRegressCleanly(): void
    {
        $this->regressDatabase();

        $this->assertFalse($this->db->tableExists($this->tables['assets']));
    }

    /**
     * @param array<string, string|null> $overrides
     */
    private function insertAssetRow(string $table, array $overrides): void
    {
        $this->hasInDatabase($table, [
            'entity_type' => 'test_entity',
            'entity_id'   => 123,
            'collection'  => 'default_collection',
            'storage'     => 'public',
            'name'        => 'Profile image',
            'file_name'   => 'profile.jpg',
            'mime_type'   => 'image/jpeg',
            'size'        => 1024,
            'path'        => 'avatars/user-123/profile.jpg',
            'order'       => 0,
            'metadata'    => null,
            'created_at'  => '2026-06-12 00:00:00',
            'updated_at'  => '2026-06-12 00:00:00',
            'deleted_at'  => null,
            ...$overrides,
        ]);
    }
}
