<?php

declare(strict_types=1);

namespace Tests\Support;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Maniaba\AssetConnect\Config\Asset;

abstract class DatabaseTestCase extends CIUnitTestCase
{
    use DatabaseTestTrait {
        setUpDatabase as private setUpDatabaseFromTrait;
    }

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
        $this->namespace = 'Maniaba\\AssetConnect';

        $this->setUpDatabaseFromTrait();
    }
}
