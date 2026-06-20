<?php

declare(strict_types=1);

namespace Tests\Storage;

use Maniaba\AssetConnect\Enums\AssetVisibility;
use Maniaba\AssetConnect\Storage\StorageLinker;
use Maniaba\AssetConnect\Storage\StorageLinkStatus;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
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

    public function testCreatesConfiguredPublicStorageLinksAndSkipsProtectedStorage(): void
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
        $this->assertSame(StorageLinkStatus::LINKED, $results[0]->status);
        $this->assertSame(StorageLinkStatus::SKIPPED, $results[1]->status);
        $this->assertLinkedTo($this->root . DIRECTORY_SEPARATOR . 'storage-public', $this->publicRoot . DIRECTORY_SEPARATOR . 'assets/storage');
        $this->assertFileDoesNotExist($this->publicRoot . DIRECTORY_SEPARATOR . 'assets/protected');
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
        $this->assertSame(StorageLinkStatus::LINKED, $results[0]->status);
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
        $this->assertSame(StorageLinkStatus::SKIPPED, $results[0]->status);
    }

    public function testSkipsRemotePublicStorageWithDirectPublicUrl(): void
    {
        $config           = new TestAssetConfig();
        $config->storages = [
            's3_public' => [
                'driver'     => 's3',
                'public_url' => 'https://cdn.example.test/assets',
                'visibility' => 'public',
            ],
        ];

        $results = (new StorageLinker($config, $this->publicRoot))->link();

        $this->assertCount(1, $results);
        $this->assertSame(StorageLinkStatus::SKIPPED, $results[0]->status);
        $this->assertSame('Storage links are only supported for local disks with a root path. Remote public disks should use public_url directly.', $results[0]->message);
    }

    public function testCanFilterSingleStorageAndReportsMissingStorage(): void
    {
        $config           = new TestAssetConfig();
        $config->storages = [
            'first' => [
                'driver'     => 'local',
                'root'       => $this->root . DIRECTORY_SEPARATOR . 'first',
                'public_url' => 'assets/first',
            ],
            'second' => [
                'driver'     => 'local',
                'root'       => $this->root . DIRECTORY_SEPARATOR . 'second',
                'public_url' => 'assets/second',
            ],
        ];

        $linker = new StorageLinker($config, $this->publicRoot);

        $results = $linker->link('second', dryRun: true);

        $this->assertCount(1, $results);
        $this->assertSame('second', $results[0]->storage);

        $results = $linker->link('missing');

        $this->assertCount(1, $results);
        $this->assertSame(StorageLinkStatus::FAILED, $results[0]->status);
        $this->assertSame('Storage disk is not configured.', $results[0]->message);
    }

    public function testSkipsInvalidStorageConfigAndNonStringPublicUrl(): void
    {
        $config = new TestAssetConfig();
        $this->setStorageConfigs($config, [
            'invalid'   => 'not-an-array',
            'array_url' => [
                'driver'     => 'local',
                'root'       => $this->root . DIRECTORY_SEPARATOR . 'array-url',
                'public_url' => ['assets/storage'],
            ],
        ]);

        $results = (new StorageLinker($config, $this->publicRoot))->link();

        $this->assertCount(2, $results);
        $this->assertSame(StorageLinkStatus::SKIPPED, $results[0]->status);
        $this->assertSame('Storage config is not an array.', $results[0]->message);
        $this->assertSame(StorageLinkStatus::SKIPPED, $results[1]->status);
        $this->assertSame('Storage disk does not define a linkable public_url.', $results[1]->message);
    }

    public function testReportsExistingStorageLink(): void
    {
        $source = $this->root . DIRECTORY_SEPARATOR . 'storage-existing';
        $target = $this->publicRoot . DIRECTORY_SEPARATOR . 'assets/existing';

        mkdir($source, 0755, true);
        mkdir(dirname($target), 0755, true);
        symlink($source, $target);

        $config           = new TestAssetConfig();
        $config->storages = [
            'public' => [
                'driver'     => 'local',
                'root'       => $source,
                'public_url' => 'assets/existing',
            ],
        ];

        $results = (new StorageLinker($config, $this->publicRoot))->link();

        $this->assertCount(1, $results);
        $this->assertSame(StorageLinkStatus::EXISTING, $results[0]->status);
        $this->assertSame('Storage link already exists.', $results[0]->message);
    }

    public function testReportsFailureWhenSourceRootCannotBeCreated(): void
    {
        $rootFile = $this->root . DIRECTORY_SEPARATOR . 'root-file';
        file_put_contents($rootFile, 'not a directory');

        $config           = new TestAssetConfig();
        $config->storages = [
            'public' => [
                'driver'     => 'local',
                'root'       => $rootFile,
                'public_url' => 'assets/root-failure',
            ],
        ];

        $results = (new StorageLinker($config, $this->publicRoot))->link();

        $this->assertSame(StorageLinkStatus::FAILED, $results[0]->status);
        $this->assertSame('Storage root directory could not be created.', $results[0]->message);
    }

    public function testReportsFailureWhenTargetAlreadyExists(): void
    {
        $source = $this->root . DIRECTORY_SEPARATOR . 'storage-target-exists';
        $target = $this->publicRoot . DIRECTORY_SEPARATOR . 'assets/target-exists';

        mkdir($source, 0755, true);
        mkdir(dirname($target), 0755, true);
        file_put_contents($target, 'existing file');

        $config           = new TestAssetConfig();
        $config->storages = [
            'public' => [
                'driver'     => 'local',
                'root'       => $source,
                'public_url' => 'assets/target-exists',
            ],
        ];

        $results = (new StorageLinker($config, $this->publicRoot))->link();

        $this->assertSame(StorageLinkStatus::FAILED, $results[0]->status);
        $this->assertSame('Target path already exists.', $results[0]->message);
    }

    public function testForceReplacesExistingDifferentSymlink(): void
    {
        $source       = $this->root . DIRECTORY_SEPARATOR . 'storage-force-source';
        $oldSource    = $this->root . DIRECTORY_SEPARATOR . 'storage-force-old-source';
        $target       = $this->publicRoot . DIRECTORY_SEPARATOR . 'assets/force-link';
        $targetParent = dirname($target);

        mkdir($source, 0755, true);
        mkdir($oldSource, 0755, true);
        mkdir($targetParent, 0755, true);
        symlink($oldSource, $target);

        $config           = new TestAssetConfig();
        $config->storages = [
            'public' => [
                'driver'     => 'local',
                'root'       => $source,
                'public_url' => 'assets/force-link',
            ],
        ];

        $results = (new StorageLinker($config, $this->publicRoot))->link(force: true);

        $this->assertSame(StorageLinkStatus::LINKED, $results[0]->status);
        $this->assertSame(realpath($source), realpath($target));
    }

    public function testReportsFailureWhenExistingSymlinkCannotBeRemoved(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('POSIX directory permissions are required for this symlink removal failure scenario.');
        }

        $source       = $this->root . DIRECTORY_SEPARATOR . 'storage-unlink-source';
        $oldSource    = $this->root . DIRECTORY_SEPARATOR . 'storage-unlink-old-source';
        $target       = $this->publicRoot . DIRECTORY_SEPARATOR . 'assets/unlink-fails';
        $targetParent = dirname($target);

        mkdir($source, 0755, true);
        mkdir($oldSource, 0755, true);
        mkdir($targetParent, 0755, true);
        symlink($oldSource, $target);
        chmod($targetParent, 0555);

        $config           = new TestAssetConfig();
        $config->storages = [
            'public' => [
                'driver'     => 'local',
                'root'       => $source,
                'public_url' => 'assets/unlink-fails',
            ],
        ];

        try {
            $results = (new StorageLinker($config, $this->publicRoot))->link(force: true);
        } finally {
            chmod($targetParent, 0755);
        }

        $this->assertSame(StorageLinkStatus::FAILED, $results[0]->status);
        $this->assertSame('Existing symlink could not be removed.', $results[0]->message);
    }

    public function testReportsFailureWhenTargetParentCannotBeCreated(): void
    {
        $source           = $this->root . DIRECTORY_SEPARATOR . 'storage-parent-fails';
        $targetParentFile = $this->publicRoot . DIRECTORY_SEPARATOR . 'assets';

        mkdir($source, 0755, true);
        file_put_contents($targetParentFile, 'not a directory');

        $config           = new TestAssetConfig();
        $config->storages = [
            'public' => [
                'driver'     => 'local',
                'root'       => $source,
                'public_url' => 'assets/parent-fails',
            ],
        ];

        $results = (new StorageLinker($config, $this->publicRoot))->link();

        $this->assertSame(StorageLinkStatus::FAILED, $results[0]->status);
        $this->assertSame('Target parent directory could not be created.', $results[0]->message);
    }

    public function testReportsFailureWhenStorageLinkCannotBeCreated(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('POSIX directory permissions are required for this symlink creation failure scenario.');
        }

        $source       = $this->root . DIRECTORY_SEPARATOR . 'storage-link-fails';
        $target       = $this->publicRoot . DIRECTORY_SEPARATOR . 'assets/link-fails';
        $targetParent = dirname($target);

        mkdir($source, 0755, true);
        mkdir($targetParent, 0755, true);
        chmod($targetParent, 0555);

        $config           = new TestAssetConfig();
        $config->storages = [
            'public' => [
                'driver'     => 'local',
                'root'       => $source,
                'public_url' => 'assets/link-fails',
            ],
        ];

        try {
            $results = (new StorageLinker($config, $this->publicRoot))->link();
        } finally {
            chmod($targetParent, 0755);
        }

        $this->assertSame(StorageLinkStatus::FAILED, $results[0]->status);
        $this->assertSame('Storage link could not be created.', $results[0]->message);
    }

    public function testProtectedVisibilityAcceptsEnumInstance(): void
    {
        $config           = new TestAssetConfig();
        $config->storages = [
            'protected' => [
                'driver'     => 'local',
                'root'       => $this->root . DIRECTORY_SEPARATOR . 'storage-protected-enum',
                'public_url' => 'assets/protected-enum',
                'visibility' => AssetVisibility::PROTECTED,
            ],
        ];

        $results = (new StorageLinker($config, $this->publicRoot))->link();

        $this->assertSame(StorageLinkStatus::SKIPPED, $results[0]->status);
        $this->assertSame('Protected storage disks are served through AssetConnect routes and should not be publicly linked.', $results[0]->message);
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

    /**
     * @param array<string, mixed> $storages
     */
    private function setStorageConfigs(TestAssetConfig $config, array $storages): void
    {
        $property = new ReflectionProperty($config, 'storages');
        $property->setValue($config, $storages);
    }
}
