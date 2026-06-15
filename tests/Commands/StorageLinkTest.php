<?php

declare(strict_types=1);

namespace Tests\Commands;

use CodeIgniter\CLI\Commands;
use Maniaba\AssetConnect\Commands\StorageLink;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(StorageLink::class)]
final class StorageLinkTest extends TestCase
{
    public function testCommandIsDiscoverable(): void
    {
        /** @var Commands $commands */
        $commands = service('commands');
        $items    = $commands->getCommands();

        $this->assertArrayHasKey('asset-connect:storage-link', $items);
        $this->assertSame(StorageLink::class, $items['asset-connect:storage-link']['class']);
        $this->assertSame('AssetConnect', $items['asset-connect:storage-link']['group']);
    }
}
