<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Database\Migrations;

use Maniaba\AssetConnect\Database\BaseMigration;
use Override;

class AddStorageToAssetsTable extends BaseMigration
{
    private const string STORAGE_INDEX                               = 'assets_storage_index';
    private const string ENTITY_TYPE_ENTITY_ID_DELETED_AT_INDEX      = 'assets_entity_type_entity_id_deleted_at_index';
    private const string ENTITY_TYPE_ENTITY_ID_COLLECTION_DELETED_AT = 'assets_entity_type_entity_id_collection_deleted_at_index';

    #[Override]
    public function up(): void
    {
        $table = $this->assetsTables('assets');

        $this->forge->addColumn($table, [
            'storage' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'comment'    => 'Storage disk name for the asset file',
                'null'       => false,
                'after'      => 'collection',
            ],
        ]);

        $this->forge->addKey('storage', false, false, self::STORAGE_INDEX);
        $this->forge->addKey(['entity_type', 'entity_id', 'deleted_at'], false, false, self::ENTITY_TYPE_ENTITY_ID_DELETED_AT_INDEX);
        $this->forge->addKey(
            ['entity_type', 'entity_id', 'collection', 'deleted_at'],
            false,
            false,
            self::ENTITY_TYPE_ENTITY_ID_COLLECTION_DELETED_AT,
        );
        $this->forge->processIndexes($table);
    }

    #[Override]
    public function down(): void
    {
        $table   = $this->assetsTables('assets');
        $db      = $this->database();
        $indexes = $db->getIndexData($table);

        if (isset($indexes[self::ENTITY_TYPE_ENTITY_ID_COLLECTION_DELETED_AT])) {
            $this->forge->dropKey($table, self::ENTITY_TYPE_ENTITY_ID_COLLECTION_DELETED_AT, false);
        }

        if (isset($indexes[self::ENTITY_TYPE_ENTITY_ID_DELETED_AT_INDEX])) {
            $this->forge->dropKey($table, self::ENTITY_TYPE_ENTITY_ID_DELETED_AT_INDEX, false);
        }

        if (isset($indexes[self::STORAGE_INDEX])) {
            $this->forge->dropKey($table, self::STORAGE_INDEX, false);
        }

        if ($db->fieldExists('storage', $table)) {
            $this->forge->dropColumn($table, 'storage');
        }
    }
}
