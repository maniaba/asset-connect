<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Database\Migrations;

use Maniaba\AssetConnect\Database\BaseMigration;
use Override;

class AddStorageToAssetsTable extends BaseMigration
{
    private const string STORAGE_INDEX      = 'assets_storage_index';
    private const string STORAGE_PATH_INDEX = 'assets_storage_path_index';

    #[Override]
    public function up(): void
    {
        $table = $this->assetsTables('assets');

        $this->forge->addColumn($table, [
            'storage' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'comment'    => 'Storage disk name for the asset file',
                'null'       => true,
                'after'      => 'collection',
            ],
        ]);

        $this->forge->addKey('storage', false, false, self::STORAGE_INDEX);
        $this->forge->addKey(['storage', 'path'], false, false, self::STORAGE_PATH_INDEX);
        $this->forge->processIndexes($table);
    }

    #[Override]
    public function down(): void
    {
        $table = $this->assetsTables('assets');

        $this->forge->dropKey($table, self::STORAGE_PATH_INDEX, false);
        $this->forge->dropKey($table, self::STORAGE_INDEX, false);
        $this->forge->dropColumn($table, 'storage');
    }
}
