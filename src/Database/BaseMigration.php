<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Database;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Forge;
use CodeIgniter\Database\Migration;
use LogicException;
use Maniaba\AssetConnect\Config\Asset;

abstract class BaseMigration extends Migration
{
    private array $tables;
    private readonly array $attributes;

    public function __construct(?Forge $forge = null)
    {
        /** @var Asset $assetConfig */
        $assetConfig = config('Asset');

        if ($assetConfig->DBGroup !== null) {
            $this->DBGroup = $assetConfig->DBGroup;
        }

        parent::__construct($forge);

        $this->tables     = $assetConfig->tables;
        $this->attributes = ($this->db->getPlatform() === 'MySQLi') ? ['ENGINE' => 'InnoDB'] : [];
    }

    protected function createTable(string $tableName, bool $ifNotExists = false, array $attributes = []): void
    {
        $tableName = $this->assetsTables($tableName);

        $this->forge->createTable($tableName, $ifNotExists, [...$attributes, ...$this->attributes]);
    }

    protected function dropTable(string $tableName, bool $ifExists = false): void
    {
        $tableName = $this->assetsTables($tableName);

        $this->forge->dropTable($tableName, $ifExists);
    }

    protected function assetsTables(string $tableName): string
    {
        return $this->tables['assets'] ?? $tableName;
    }

    protected function database(): BaseConnection
    {
        if (! $this->db instanceof BaseConnection) {
            throw new LogicException('AssetConnect migrations require a CodeIgniter base database connection.');
        }

        return $this->db;
    }
}
