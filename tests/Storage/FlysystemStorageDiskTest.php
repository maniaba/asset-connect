<?php

declare(strict_types=1);

namespace Tests\Storage;

use CodeIgniter\Test\CIUnitTestCase;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Maniaba\AssetConnect\Enums\AssetVisibility;
use Maniaba\AssetConnect\Storage\FlysystemStorageDisk;
use Override;

/**
 * @internal
 */
final class FlysystemStorageDiskTest extends CIUnitTestCase
{
    private string $localRoot;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->localRoot = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . 'asset-connect-flysystem-disk-test-' . bin2hex(random_bytes(4));
    }

    #[Override]
    protected function tearDown(): void
    {
        $this->removeDirectory($this->localRoot);

        parent::tearDown();
    }

    public function testNameAndVisibility(): void
    {
        $disk = new FlysystemStorageDisk(
            'public',
            $this->createStub(FilesystemOperator::class),
            AssetVisibility::PUBLIC,
        );

        $this->assertSame('public', $disk->name());
        $this->assertSame(AssetVisibility::PUBLIC, $disk->visibility());
    }

    public function testDelegatesReadWriteMetadataAndNormalizesPaths(): void
    {
        $filesystem = $this->createMock(FilesystemOperator::class);
        $stream     = fopen('php://temp', 'rb+');

        $this->assertIsResource($stream);

        $filesystem->expects($this->once())
            ->method('write')
            ->with('nested/file.txt', 'contents', ['visibility' => 'public']);

        $filesystem->expects($this->once())
            ->method('writeStream')
            ->with('streams/file.txt', $stream, ['visibility' => 'private']);

        $filesystem->expects($this->once())
            ->method('read')
            ->with('nested/file.txt')
            ->willReturn('contents');

        $filesystem->expects($this->once())
            ->method('readStream')
            ->with('nested/file.txt')
            ->willReturn($stream);

        $filesystem->expects($this->once())
            ->method('fileExists')
            ->with('nested/file.txt')
            ->willReturn(true);

        $filesystem->expects($this->once())
            ->method('fileSize')
            ->with('nested/file.txt')
            ->willReturn(8);

        $filesystem->expects($this->once())
            ->method('mimeType')
            ->with('nested/file.txt')
            ->willReturn('text/plain');

        $filesystem->expects($this->once())
            ->method('lastModified')
            ->with('nested/file.txt')
            ->willReturn(1234567890);

        $disk = new FlysystemStorageDisk('public', $filesystem, AssetVisibility::PUBLIC);

        $disk->write('\\nested\\file.txt', 'contents', ['visibility' => 'public']);
        $disk->writeStream('/streams/file.txt', $stream, ['visibility' => 'private']);

        $this->assertSame('contents', $disk->read('/nested/file.txt'));
        $this->assertSame($stream, $disk->readStream('\\nested\\file.txt'));
        $this->assertTrue($disk->fileExists('/nested/file.txt'));
        $this->assertSame(8, $disk->fileSize('/nested/file.txt'));
        $this->assertSame('text/plain', $disk->mimeType('\\nested\\file.txt'));
        $this->assertSame(1234567890, $disk->lastModified('/nested/file.txt'));

        fclose($stream);
    }

    public function testPublicUrlUsesConcreteFlysystemImplementation(): void
    {
        $filesystem = new Filesystem(
            new LocalFilesystemAdapter($this->localRoot),
            ['public_url' => 'assets/storage'],
        );

        $disk = new FlysystemStorageDisk('public', $filesystem, AssetVisibility::PUBLIC);

        $this->assertSame('assets/storage/nested/file.txt', $disk->publicUrl('\\nested\\file.txt'));
    }

    public function testDeleteSkipsMissingFile(): void
    {
        $filesystem = $this->createMock(FilesystemOperator::class);

        $filesystem->expects($this->once())
            ->method('fileExists')
            ->with('missing/file.txt')
            ->willReturn(false);

        $filesystem->expects($this->never())
            ->method('delete');

        $disk = new FlysystemStorageDisk('public', $filesystem, AssetVisibility::PUBLIC);

        $disk->delete('/missing/file.txt');
    }

    public function testDeleteRemovesExistingFile(): void
    {
        $filesystem = $this->createMock(FilesystemOperator::class);

        $filesystem->expects($this->once())
            ->method('fileExists')
            ->with('existing/file.txt')
            ->willReturn(true);

        $filesystem->expects($this->once())
            ->method('delete')
            ->with('existing/file.txt');

        $disk = new FlysystemStorageDisk('public', $filesystem, AssetVisibility::PUBLIC);

        $disk->delete('\\existing\\file.txt');
    }

    public function testLocalPathReturnsNullForNonLocalDisk(): void
    {
        $disk = new FlysystemStorageDisk(
            'remote',
            $this->createStub(FilesystemOperator::class),
            AssetVisibility::PUBLIC,
        );

        $this->assertNull($disk->localPath('file.txt'));
    }

    public function testLocalPathCreatesDirectoryAndNormalizesPath(): void
    {
        $disk = new FlysystemStorageDisk(
            'local',
            $this->createStub(FilesystemOperator::class),
            AssetVisibility::PUBLIC,
            $this->localRoot,
        );

        $path = $disk->localPath('\\nested\\folder\\file.txt');

        $this->assertSame($this->localRoot . DIRECTORY_SEPARATOR . 'nested' . DIRECTORY_SEPARATOR . 'folder' . DIRECTORY_SEPARATOR . 'file.txt', $path);
        $this->assertDirectoryExists($this->localRoot . DIRECTORY_SEPARATOR . 'nested' . DIRECTORY_SEPARATOR . 'folder');
    }

    private function removeDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $items = scandir($directory);
        $this->assertIsArray($items);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $item;

            if (is_dir($path)) {
                $this->removeDirectory($path);

                continue;
            }

            unlink($path);
        }

        rmdir($directory);
    }
}
