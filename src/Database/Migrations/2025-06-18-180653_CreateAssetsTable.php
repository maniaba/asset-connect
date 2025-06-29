<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Database\Migrations;

use Maniaba\FileConnect\Database\BaseMigration;

class CreateAssetsTable extends BaseMigration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'entity_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 32,
                'comment'    => 'Type of the entity md5 hash of the class',
                'null'       => false,
            ],
            'entity_id' => [
                'type'     => 'INT',
                'comment'  => 'ID of the entity to which the asset belongs',
                'unsigned' => true,
            ],
            'collection' => [
                'type'       => 'VARCHAR',
                'constraint' => 32,
                'comment'    => 'Collection name for the asset md5 hash of the collection name',
                'null'       => false,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'comment'    => 'Name of the asset',
                'constraint' => 255,
            ],
            'file_name' => [
                'type'       => 'VARCHAR',
                'comment'    => 'Original file name of the asset',
                'constraint' => 255,
            ],
            'mime_type' => [
                'type'       => 'VARCHAR',
                'comment'    => 'MIME type of the asset',
                'constraint' => 255,
            ],
            'size' => [
                'type'     => 'BIGINT',
                'comment'  => 'Size of the asset in bytes',
                'unsigned' => true,
            ],
            'path' => [
                'type'       => 'VARCHAR',
                'comment'    => 'Path to the asset file',
                'constraint' => 1020,
            ],
            'order' => [
                'type'     => 'INT',
                'comment'  => 'Order of the asset in the collection',
                'unsigned' => true,
                'default'  => 0,
            ],
            'properties' => [
                'type'    => 'TEXT',
                'comment' => 'JSON encoded custom properties for the asset',
                'null'    => true,
            ],
            'created_at' => [
                'type'    => 'DATETIME',
                'comment' => 'Timestamp when the asset was created',
                'null'    => false,
            ],
            'updated_at' => [
                'type'    => 'DATETIME',
                'comment' => 'Timestamp when the asset was last updated',
                'null'    => false,
            ],
            'deleted_at' => [
                'type'    => 'DATETIME',
                'comment' => 'Timestamp when the asset was deleted',
                'null'    => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['entity_type', 'entity_id']);
        $this->forge->addKey('collection');
        $this->forge->addKey('deleted_at');

        $this->forge->addKey(['entity_type', 'entity_id', 'collection']);

        $this->createTable('assets', true);
    }

    public function down(): void
    {
        $this->dropTable('assets');
    }
}
