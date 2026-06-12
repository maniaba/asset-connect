<?php

declare(strict_types=1);

namespace Tests\Storage;

use CodeIgniter\Config\Factories;
use CodeIgniter\Test\CIUnitTestCase;
use League\Flysystem\FilesystemOperator;
use Maniaba\AssetConnect\Config\Asset;
use Maniaba\AssetConnect\Enums\AssetVisibility;
use Maniaba\AssetConnect\Exceptions\InvalidArgumentException;
use Maniaba\AssetConnect\Storage\Interfaces\StorageDiskInterface;
use Maniaba\AssetConnect\Storage\StorageManager;
use Tests\Support\Config\TestAssetConfig;

/**
 * @internal
 */
final class StorageManagerTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Factories::injectMock('config', Asset::class, new TestAssetConfig());
    }

    public function testDefaultDiskNameForVisibility(): void
    {
        $manager = StorageManager::make();

        $this->assertSame('public', $manager->defaultDiskNameForVisibility(AssetVisibility::PUBLIC));
        $this->assertSame('protected', $manager->defaultDiskNameForVisibility(AssetVisibility::PROTECTED));
    }

    public function testDiskReturnsCachedInstance(): void
    {
        $manager = StorageManager::make();

        $this->assertSame($manager->disk('public'), $manager->disk('public'));
    }

    public function testDiskReturnsConfiguredStorageDiskInstance(): void
    {
        $disk   = $this->createStub(StorageDiskInterface::class);
        $config = new TestAssetConfig();

        $config->storages = [
            'custom' => [
                'disk' => $disk,
            ],
        ];

        $manager = new StorageManager($config);

        $this->assertSame($disk, $manager->disk('custom'));
    }

    public function testDiskWrapsConfiguredFilesystemOperator(): void
    {
        $config = new TestAssetConfig();

        $config->storages = [
            'remote' => [
                'filesystem' => $this->createStub(FilesystemOperator::class),
                'visibility' => AssetVisibility::PROTECTED,
            ],
        ];

        $disk = (new StorageManager($config))->disk('remote');

        $this->assertSame('remote', $disk->name());
        $this->assertSame(AssetVisibility::PROTECTED, $disk->visibility());
        $this->assertNull($disk->localPath('file.txt'));
    }

    public function testDiskThrowsWhenStorageIsNotConfigured(): void
    {
        try {
            StorageManager::make()->disk('missing');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame(["Storage disk 'missing' is not configured."], $exception->errors);

            return;
        }

        $this->fail('Expected invalid storage disk exception.');
    }

    public function testDiskThrowsWhenDriverIsUnsupportedWithoutFilesystemOperator(): void
    {
        $config = new TestAssetConfig();

        $config->storages = [
            's3' => [
                'driver' => 's3',
            ],
        ];

        try {
            (new StorageManager($config))->disk('s3');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame(["Storage disk 's3' uses unsupported driver 's3'. Provide a FilesystemOperator or StorageDiskInterface instance."], $exception->errors);

            return;
        }

        $this->fail('Expected unsupported storage driver exception.');
    }

    public function testDiskThrowsWhenLocalRootIsMissing(): void
    {
        $config = new TestAssetConfig();

        $config->storages = [
            'local' => [
                'driver' => 'local',
                'root'   => '',
            ],
        ];

        try {
            (new StorageManager($config))->disk('local');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame(["Local storage disk 'local' must define a non-empty root path."], $exception->errors);

            return;
        }

        $this->fail('Expected missing local root exception.');
    }

    public function testLocalDiskWritesReadsAndResolvesPublicUrl(): void
    {
        $disk = StorageManager::make()->disk('public');
        $path = 'storage-manager/example.txt';

        $disk->write($path, 'asset-connect');

        $this->assertTrue($disk->fileExists($path));
        $this->assertSame('asset-connect', $disk->read($path));
        $this->assertSame(13, $disk->fileSize($path));
        $this->assertSame('assets/storage/storage-manager/example.txt', $disk->publicUrl($path));
        $this->assertSame(HOMEPATH . 'build/asset-connect/public/storage-manager/example.txt', $disk->localPath($path));
    }

    public function testDeleteIgnoresMissingFiles(): void
    {
        $disk = StorageManager::make()->disk('public');

        $disk->delete('storage-manager/missing.txt');

        $this->assertFalse($disk->fileExists('storage-manager/missing.txt'));
    }
}
