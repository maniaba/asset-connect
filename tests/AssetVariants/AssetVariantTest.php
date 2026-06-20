<?php

declare(strict_types=1);

namespace Tests\AssetVariants;

use CodeIgniter\Config\Factories;
use CodeIgniter\Test\CIUnitTestCase;
use Maniaba\AssetConnect\AssetVariants\AssetVariant;
use Maniaba\AssetConnect\Config\Asset;
use Maniaba\AssetConnect\Enums\AssetVisibility;
use Maniaba\AssetConnect\Exceptions\FileVariantException;
use Maniaba\AssetConnect\Storage\Interfaces\StorageDiskInterface;
use RuntimeException;
use Tests\Support\Config\TestAssetConfig;

/**
 * @internal
 */
final class AssetVariantTest extends CIUnitTestCase
{
    private AssetVariant $assetVariant;

    protected function setUp(): void
    {
        parent::setUp();

        $config = new TestAssetConfig();
        Factories::injectMock('config', Asset::class, $config);
        Factories::injectMock('config', 'Asset', $config);

        $this->assetVariant = new AssetVariant([
            'storage' => 'public',
        ]);
    }

    public function testWriteFileSuccessfully(): void
    {
        $this->assetVariant->path = 'variants/write_method/file.txt';
        $data                     = 'file content';

        $result = $this->assetVariant->writeFile($data);

        $this->assertTrue($result);
        $this->assertSame(12, $this->assetVariant->size);
        $this->assertTrue($this->assetVariant->processed);
        $this->assertFileExists(HOMEPATH . 'build/asset-connect/public/variants/write_method/file.txt');
    }

    public function testWriteFileWrapsStorageWriteFailure(): void
    {
        $previous = new RuntimeException('Storage write failed.');
        $disk     = $this->createMock(StorageDiskInterface::class);
        $disk->expects($this->once())
            ->method('write')
            ->with('variants/failing-write.txt', 'file content')
            ->willThrowException($previous);
        $disk->expects($this->never())->method('fileSize');

        $this->configureVariantStorageDisk('failing', $disk);

        $variant = new AssetVariant([
            'storage' => 'failing',
            'path'    => 'variants/failing-write.txt',
        ]);

        try {
            $variant->writeFile('file content');
            $this->fail('Variant write should throw when the storage disk write fails.');
        } catch (FileVariantException $exception) {
            $this->assertSame('Failed to write file to storage path: failing:variants/failing-write.txt', $exception->getMessage(), 'Variant write failure should include storage and path context.');
            $this->assertSame($previous, $exception->getPrevious(), 'Variant write failure should keep the original storage exception.');
        }
    }

    public function testCopyToTemporaryFileReadsRemoteStorage(): void
    {
        $disk = $this->createMock(StorageDiskInterface::class);
        $disk->method('name')->willReturn('remote');
        $disk->method('visibility')->willReturn(AssetVisibility::PUBLIC);
        $disk->expects($this->once())
            ->method('fileExists')
            ->with('variants/remote-preview.webp')
            ->willReturn(true);
        $disk->expects($this->once())
            ->method('readStream')
            ->with('variants/remote-preview.webp')
            ->willReturn($this->streamFromString('variant contents'));

        $this->configureVariantStorageDisk('remote', $disk);

        $variant = new AssetVariant([
            'storage' => 'remote',
            'path'    => 'variants/remote-preview.webp',
        ]);

        $temporaryFile = $variant->copyToTemporaryFile();

        try {
            $this->assertFileExists($temporaryFile);
            $this->assertStringEndsWith('.webp', $temporaryFile);
            $this->assertSame('variant contents', file_get_contents($temporaryFile));
        } finally {
            @unlink($temporaryFile);
        }
    }

    public function testStorageMetadataAccessorsReturnDiskValues(): void
    {
        $disk = $this->createMock(StorageDiskInterface::class);
        $disk->expects($this->once())
            ->method('fileExists')
            ->with('variants/metadata-preview.jpg')
            ->willReturn(true);
        $disk->expects($this->once())
            ->method('mimeType')
            ->with('variants/metadata-preview.jpg')
            ->willReturn('image/jpeg');
        $disk->expects($this->once())
            ->method('localPath')
            ->with('variants/metadata-preview.jpg')
            ->willReturn('/tmp/metadata-preview.jpg');

        $this->configureVariantStorageDisk('metadata', $disk);

        $variant = new AssetVariant([
            'storage' => 'metadata',
            'path'    => 'variants/metadata-preview.jpg',
        ]);

        $this->assertSame('metadata-preview.jpg', $variant->file_name, 'Variant file_name accessor should return the basename of the path.');
        $this->assertSame('jpg', $variant->extension, 'Variant extension accessor should return the path extension.');
        $this->assertSame('image/jpeg', $variant->mime_type, 'Variant mime_type accessor should read the storage disk MIME type.');
        $this->assertSame('/tmp/metadata-preview.jpg', $variant->local_path, 'Variant local_path accessor should return the storage disk local path.');
    }

    public function testMimeTypeReturnsEmptyWhenVariantFileIsMissing(): void
    {
        $disk = $this->createMock(StorageDiskInterface::class);
        $disk->expects($this->once())
            ->method('fileExists')
            ->with('variants/missing.jpg')
            ->willReturn(false);
        $disk->expects($this->never())->method('mimeType');

        $this->configureVariantStorageDisk('missing', $disk);

        $variant = new AssetVariant([
            'storage' => 'missing',
            'path'    => 'variants/missing.jpg',
        ]);

        $this->assertSame('', $variant->mime_type, 'Variant mime_type accessor should return an empty string when the file does not exist.');
    }

    public function testWithTemporaryFileReturnsCallbackResultAndDeletesTemporaryFile(): void
    {
        $disk = $this->createMock(StorageDiskInterface::class);
        $disk->method('name')->willReturn('remote');
        $disk->expects($this->once())
            ->method('fileExists')
            ->with('variants/callback-preview.webp')
            ->willReturn(true);
        $disk->expects($this->once())
            ->method('readStream')
            ->with('variants/callback-preview.webp')
            ->willReturn($this->streamFromString('callback contents'));

        $this->configureVariantStorageDisk('callback', $disk);

        $variant = new AssetVariant([
            'storage' => 'callback',
            'path'    => 'variants/callback-preview.webp',
        ]);

        $temporaryFilePath = null;
        $result            = $variant->withTemporaryFile(static function (string $temporaryFile) use (&$temporaryFilePath): string {
            $temporaryFilePath = $temporaryFile;

            return (string) file_get_contents($temporaryFile);
        });

        $this->assertSame('callback contents', $result, 'withTemporaryFile should return the callback result.');
        $this->assertIsString($temporaryFilePath, 'withTemporaryFile callback should receive a temporary file path.');
        $this->assertFileDoesNotExist($temporaryFilePath, 'withTemporaryFile should delete the temporary file after the callback finishes.');
    }

    public function testGetRelativePathReturnsCorrectPath(): void
    {
        $this->assetVariant->path = 'path/to/file.jpg';

        $method = $this->getPrivateMethodInvoker($this->assetVariant, 'getRelativePath');

        $result = $method($this->assetVariant);

        $this->assertSame('/path/to/file.jpg', $result);
    }

    public function testGetRelativePathThrowsExceptionWhenPathIsNotSet(): void
    {
        $this->assetVariant->path = '';

        $invoker = $this->getPrivateMethodInvoker($this->assetVariant, 'getRelativePath');

        $this->expectException(FileVariantException::class);
        $invoker();
    }

    public function testGetRelativePathForUrlReturnsCorrectUrlPath(): void
    {
        $this->assetVariant->path = 'path/to/file.jpg';

        $invoker = $this->getPrivateMethodInvoker($this->assetVariant, 'getRelativePathForUrl');

        $result = $invoker();

        $this->assertSame('/path/to/file.jpg', $result);
    }

    public function testGetRelativePathForUrlReplacesBackslashesWithForwardSlashes(): void
    {
        $this->assetVariant->path = 'path\\to\\file.jpg';

        $getRelativePathMethod = $this->getPrivateMethodInvoker($this->assetVariant, 'getRelativePath');
        $relativePath          = (string) $getRelativePathMethod();

        $this->assertStringNotContainsString('\\', $relativePath);

        $getRelativePathForUrlMethod = $this->getPrivateMethodInvoker($this->assetVariant, 'getRelativePathForUrl');

        $result = (string) $getRelativePathForUrlMethod();

        $this->assertStringNotContainsString('\\', $result);
        $this->assertStringContainsString('/', $result);
    }

    private function configureVariantStorageDisk(string $name, StorageDiskInterface $disk): void
    {
        $config                  = new TestAssetConfig();
        $config->storages[$name] = [
            'disk' => $disk,
        ];

        Factories::injectMock('config', Asset::class, $config);
        Factories::injectMock('config', 'Asset', $config);
    }

    /**
     * @return resource
     */
    private function streamFromString(string $contents)
    {
        $stream = fopen('php://temp', 'rb+');

        $this->assertIsResource($stream, 'Temporary stream should be created for remote storage simulation.');
        fwrite($stream, $contents);
        rewind($stream);

        return $stream;
    }
}
