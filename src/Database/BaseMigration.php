<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Database;

use CodeIgniter\Database\Forge;
use CodeIgniter\Database\Migration;
use Maniaba\FileConnect\Config\Asset;

abstract class BaseMigration extends Migration
{
    private array $tables;
    private readonly array $attributes;

    public function __construct(?Forge $forge = null)
    {
        /** @var Asset $authConfig */
        $authConfig = config('Auth');

        if ($authConfig->DBGroup !== null) {
            $this->DBGroup = $authConfig->DBGroup;
        }

        parent::__construct($forge);

        $this->tables     = $authConfig->tables;
        $this->attributes = ($this->db->getPlatform() === 'MySQLi') ? ['ENGINE' => 'InnoDB'] : [];
    }

    protected function createTable(string $tableName, bool $ifNotExists = false, array $attributes = []): void
    {
        $tableName = $this->tables[$tableName] ?? $tableName;

        $this->forge->createTable($tableName, $ifNotExists, [...$attributes, ...$this->attributes]);
    }

    protected function dropTable(string $tableName, bool $ifExists = false): void
    {
        $tableName = $this->tables[$tableName] ?? $tableName;

        $this->forge->dropTable($tableName, $ifExists);
    }
}
