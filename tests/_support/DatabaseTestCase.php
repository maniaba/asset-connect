<?php

declare(strict_types=1);

namespace Tests\Support;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\Fabricator;
use Maniaba\AssetConnect\Config\Asset;

abstract class DatabaseTestCase extends CIUnitTestCase
{
    use DatabaseTestTrait;

    /**
     * @var list<string>
     */
    protected array $migrationNamespaces = [
        'Maniaba\\AssetConnect',
    ];

    /**
     * @var array<string, string>
     */
    protected array $tables;

    protected function setUp(): void
    {
        if (! extension_loaded('sqlite3')) {
            $this->markTestSkipped('The sqlite3 PHP extension is required for database tests.');
        }

        parent::setUp();

        /** @var Asset $assetConfig */
        $assetConfig  = config('Asset');
        $this->tables = $assetConfig->tables;
    }

    protected function setUpDatabase(): void
    {
        $this->configureDatabaseTest();

        $this->loadDependencies();

        if ($this->refresh === true) {
            $this->namespace = null;
            $this->regressDatabase();

            Fabricator::resetCounts();
        }

        foreach ($this->migrationNamespaces as $namespace) {
            $this->namespace = $namespace;

            $this->migrateDatabase();
        }

        $this->setUpSeed();
    }

    protected function configureDatabaseTest(): void
    {
    }
}
