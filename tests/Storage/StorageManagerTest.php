<?php

declare(strict_types=1);

namespace Tests\Storage;

use CodeIgniter\Config\Factories;
use CodeIgniter\Test\CIUnitTestCase;
use Maniaba\AssetConnect\Config\Asset;
use Maniaba\AssetConnect\Enums\AssetVisibility;
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
