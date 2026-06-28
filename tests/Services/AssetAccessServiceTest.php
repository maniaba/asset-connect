<?php

declare(strict_types=1);

namespace Tests\Services;

use CodeIgniter\Config\Factories;
use CodeIgniter\Test\CIUnitTestCase;
use Maniaba\AssetConnect\Config\Asset as AssetConfig;
use Maniaba\AssetConnect\Enums\AssetVisibility;
use Maniaba\AssetConnect\Exceptions\PageException;
use Maniaba\AssetConnect\Pending\DefaultPendingStorage;
use Maniaba\AssetConnect\Pending\PendingAsset;
use Maniaba\AssetConnect\Repositories\Interfaces\AssetRepositoryInterface;
use Maniaba\AssetConnect\Services\AssetAccessService;
use Maniaba\AssetConnect\Storage\Interfaces\StorageDiskInterface;
use Tests\Support\Config\TestAssetConfig;

/**
 * @internal
 */
final class AssetAccessServiceTest extends CIUnitTestCase
{
    private string $storageRoot;
    private string $sourcePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->storageRoot = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . 'asset-connect-access-service-test-' . bin2hex(random_bytes(4));
        $config                       = new TestAssetConfig();
        $config->pendingSecurityToken = null;
        $config->storages             = [
            'protected' => [
                'driver'     => 'local',
                'root'       => $this->storageRoot,
                'visibility' => 'protected',
            ],
        ];
        Factories::injectMock('config', AssetConfig::class, $config);
        Factories::injectMock('config', 'Asset', $config);
        $sourcePath = tempnam(sys_get_temp_dir(), 'asset_access_pending_');
        $this->assertIsString($sourcePath);
        $this->sourcePath = $sourcePath;
        file_put_contents($this->sourcePath, 'pending response content');
    }

    protected function tearDown(): void
    {
        if (is_file($this->sourcePath)) {
            unlink($this->sourcePath);
        }
        if (is_dir($this->storageRoot)) {
            helper('filesystem');
            delete_files($this->storageRoot, true, true, true);
            @rmdir($this->storageRoot);
        }
        Factories::reset('config');
        parent::tearDown();
    }

    public function testHandlePendingAssetRequestReturnsDownloadResponse(): void
    {
        $pendingAsset = PendingAsset::createFromFile($this->sourcePath);
        $pendingAsset->usingFileName('pending-response.txt');

        (new DefaultPendingStorage())->store($pendingAsset, 'pending-response-id');

        $service  = new AssetAccessService($this->createStub(AssetRepositoryInterface::class));
        $response = $service->handlePendingAssetRequest('pending-response-id');

        $this->assertSame(strlen('pending response content'), $response->getContentLength());
        $this->assertSame((string) strlen('pending response content'), $response->getHeaderLine('Content-Length'));
        $this->assertNotSame('', $response->getHeaderLine('Last-Modified'));
    }

    public function testHandlePendingAssetRequestStreamsRemotePendingStorageToTemporaryFile(): void
    {
        $disk = $this->createMock(StorageDiskInterface::class);
        $disk->method('visibility')->willReturn(AssetVisibility::PROTECTED);
        $disk->method('fileExists')->willReturn(true);
        $disk->method('read')->willReturn((string) json_encode([
            'id'        => 'remote-pending-id',
            'file_name' => 'remote-pending.txt',
            'mime_type' => 'text/plain',
            'size'      => strlen('remote pending content'),
        ]));
        $disk->method('readStream')->willReturnCallback(static function () {
            $stream = fopen('php://temp', 'rb+');
            fwrite($stream, 'remote pending content');
            rewind($stream);

            return $stream;
        });
        $disk->expects($this->never())->method('localPath');

        $config                       = new TestAssetConfig();
        $config->pendingSecurityToken = null;
        $config->storages             = [
            'protected' => [
                'disk'       => $disk,
                'visibility' => 'protected',
            ],
        ];
        Factories::injectMock('config', AssetConfig::class, $config);
        Factories::injectMock('config', 'Asset', $config);

        $service  = new AssetAccessService($this->createStub(AssetRepositoryInterface::class));
        $response = $service->handlePendingAssetRequest('remote-pending-id');

        $this->assertSame(strlen('remote pending content'), $response->getContentLength());
        $this->assertSame('text/plain; charset=UTF-8', $response->getHeaderLine('Content-Type'));
    }

    public function testHandlePendingAssetRequestThrowsWhenPendingAssetIsMissing(): void
    {
        $service = new AssetAccessService($this->createStub(AssetRepositoryInterface::class));

        $this->expectException(PageException::class);
        $this->expectExceptionCode(404);

        $service->handlePendingAssetRequest('missing-pending-id');
    }
}
