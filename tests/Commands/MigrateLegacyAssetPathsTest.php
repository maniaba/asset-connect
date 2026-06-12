<?php

declare(strict_types=1);

namespace Tests\Commands;

use CodeIgniter\CLI\Commands;
use Maniaba\AssetConnect\Commands\MigrateLegacyAssetPaths;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(MigrateLegacyAssetPaths::class)]
final class MigrateLegacyAssetPathsTest extends TestCase
{
    public function testCommandIsDiscoverable(): void
    {
        /** @var Commands $commands */
        $commands = service('commands');
        $items    = $commands->getCommands();

        $this->assertArrayHasKey('asset-connect:migrate-paths', $items);
        $this->assertSame(MigrateLegacyAssetPaths::class, $items['asset-connect:migrate-paths']['class']);
        $this->assertSame('AssetConnect', $items['asset-connect:migrate-paths']['group']);
    }
}
