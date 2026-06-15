<?php

declare(strict_types=1);

namespace Tests\Database\Migrations;

use Config\Database;
use Maniaba\AssetConnect\Database\Migrations\AddStorageToAssetsTable;
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
        $this->assertArrayHasKey('assets_entity_type_entity_id_deleted_at_index', $indexes);
        $this->assertArrayHasKey('assets_entity_type_entity_id_collection_deleted_at_index', $indexes);
        $this->assertSame(['storage'], $indexes['assets_storage_index']->fields);
        $this->assertSame(['entity_type', 'entity_id', 'deleted_at'], $indexes['assets_entity_type_entity_id_deleted_at_index']->fields);
        $this->assertSame(['entity_type', 'entity_id', 'collection', 'deleted_at'], $indexes['assets_entity_type_entity_id_collection_deleted_at_index']->fields);
    }

    public function testAssetsTableAllowsLegacyRowsWithoutStorage(): void
    {
        $table = $this->tables['assets'];

        $this->insertAssetRow($table, [
            'storage' => '',
            'path'    => '/legacy/full/path.jpg',
        ]);

        $row = $this->db->table($table)
            ->where('path', '/legacy/full/path.jpg')
            ->get()
            ->getRowArray();

        $this->assertIsArray($row);
        $this->assertSame('', $row['storage']);
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

    public function testStorageMigrationCanResumeWhenStorageColumnAlreadyExists(): void
    {
        $table = $this->tables['assets'];
        $forge = Database::forge('tests');

        (new AddStorageToAssetsTable($forge))->down();
        (new AddStorageToAssetsTable($forge))->up();

        $indexes = $this->db->getIndexData($table);

        $this->assertTrue($this->db->fieldExists('storage', $table));
        $this->assertArrayHasKey('assets_storage_index', $indexes);
        $this->assertArrayHasKey('assets_entity_type_entity_id_deleted_at_index', $indexes);
        $this->assertArrayHasKey('assets_entity_type_entity_id_collection_deleted_at_index', $indexes);
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
