<?php

declare(strict_types=1);

namespace Tests\Storage;

use Maniaba\AssetConnect\Storage\StorageLinker;
use Maniaba\AssetConnect\Storage\StorageLinkResult;
use PHPUnit\Framework\TestCase;
use Tests\Support\Config\TestAssetConfig;

/**
 * @internal
 */
final class StorageLinkerTest extends TestCase
{
    private string $root;
    private string $publicRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root       = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'asset-connect-linker-' . bin2hex(random_bytes(6));
        $this->publicRoot = $this->root . DIRECTORY_SEPARATOR . 'public';

        mkdir($this->publicRoot, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->root);

        parent::tearDown();
    }

    public function testCreatesConfiguredStorageLinks(): void
    {
        $config           = new TestAssetConfig();
        $config->storages = [
            'public' => [
                'driver'     => 'local',
                'root'       => $this->root . DIRECTORY_SEPARATOR . 'storage-public',
                'public_url' => 'assets/storage',
                'visibility' => 'public',
            ],
            'protected' => [
                'driver'     => 'local',
                'root'       => $this->root . DIRECTORY_SEPARATOR . 'storage-protected',
                'public_url' => 'assets/protected',
                'visibility' => 'protected',
            ],
        ];

        $results = (new StorageLinker($config, $this->publicRoot))->link();

        $this->assertCount(2, $results);
        $this->assertSame(StorageLinkResult::STATUS_LINKED, $results[0]->status);
        $this->assertSame(StorageLinkResult::STATUS_LINKED, $results[1]->status);
        $this->assertLinkedTo($this->root . DIRECTORY_SEPARATOR . 'storage-public', $this->publicRoot . DIRECTORY_SEPARATOR . 'assets/storage');
        $this->assertLinkedTo($this->root . DIRECTORY_SEPARATOR . 'storage-protected', $this->publicRoot . DIRECTORY_SEPARATOR . 'assets/protected');
    }

    public function testDryRunDoesNotCreateLinks(): void
    {
        $config           = new TestAssetConfig();
        $config->storages = [
            'public' => [
                'driver'     => 'local',
                'root'       => $this->root . DIRECTORY_SEPARATOR . 'storage-public',
                'public_url' => 'assets/storage',
                'visibility' => 'public',
            ],
        ];

        $results = (new StorageLinker($config, $this->publicRoot))->link(dryRun: true);

        $this->assertCount(1, $results);
        $this->assertSame(StorageLinkResult::STATUS_LINKED, $results[0]->status);
        $this->assertFileDoesNotExist($this->publicRoot . DIRECTORY_SEPARATOR . 'assets/storage');
    }

    public function testSkipsStorageWithoutLinkablePublicUrl(): void
    {
        $config           = new TestAssetConfig();
        $config->storages = [
            'remote' => [
                'driver'     => 'local',
                'root'       => $this->root . DIRECTORY_SEPARATOR . 'remote',
                'public_url' => 'https://cdn.example.test/assets',
            ],
        ];

        $results = (new StorageLinker($config, $this->publicRoot))->link();

        $this->assertCount(1, $results);
        $this->assertSame(StorageLinkResult::STATUS_SKIPPED, $results[0]->status);
    }

    private function assertLinkedTo(string $source, string $target): void
    {
        $this->assertDirectoryExists($source);
        $this->assertFileExists($target);
        $this->assertSame(realpath($source), realpath($target));
    }

    private function removeDirectory(string $directory): void
    {
        if (! file_exists($directory) && ! is_link($directory)) {
            return;
        }

        if (is_link($directory) || is_file($directory)) {
            unlink($directory);

            return;
        }

        $items = scandir($directory);
        $this->assertIsArray($items);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $this->removeDirectory($directory . DIRECTORY_SEPARATOR . $item);
        }

        rmdir($directory);
    }
}
