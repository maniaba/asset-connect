<?php

declare(strict_types=1);

namespace Tests\Storage;

use CodeIgniter\Test\CIUnitTestCase;
use Maniaba\AssetConnect\Exceptions\FileException;
use Maniaba\AssetConnect\Storage\Interfaces\StorageDiskInterface;
use Maniaba\AssetConnect\Storage\TemporaryStorageFile;
use RuntimeException;
use Tests\Support\Files\FailingReadStreamWrapper;
use Tests\Support\Storage\TemporaryStorageFileFunctionOverrides;

/**
 * @internal
 */
final class TemporaryStorageFileTest extends CIUnitTestCase
{
    private string $temporaryDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->temporaryDirectory = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . 'asset-connect-temporary-storage-file-test-' . bin2hex(random_bytes(4));

        mkdir($this->temporaryDirectory, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->temporaryDirectory);

        parent::tearDown();
    }

    public function testCopyFromStorageCreatesTemporaryFileWithNormalizedExtension(): void
    {
        $disk = $this->createDiskWithReadStream('stored contents');

        $temporaryFile = TemporaryStorageFile::copyFromStorage(
            $disk,
            'documents/report.txt',
            '.txt',
            $this->temporaryDirectory,
            'asset_test_',
        );

        try {
            $this->assertFileExists($temporaryFile, 'copyFromStorage should create a local temporary file.');
            $this->assertStringStartsWith($this->temporaryDirectory . DIRECTORY_SEPARATOR . 'asset_test_', $temporaryFile, 'Temporary file should be created in the requested directory with the requested prefix.');
            $this->assertStringEndsWith('.txt', $temporaryFile, 'Temporary file should preserve a normalized extension when provided.');
            $this->assertSame('stored contents', file_get_contents($temporaryFile), 'Temporary file should contain the stream contents from storage.');
        } finally {
            if (is_file($temporaryFile)) {
                unlink($temporaryFile);
            }
        }
    }

    public function testWithTemporaryFileReturnsCallbackResultAndDeletesTemporaryFile(): void
    {
        $disk              = $this->createDiskWithReadStream('callback contents');
        $callbackTemporary = null;

        $result = TemporaryStorageFile::withTemporaryFile(
            $disk,
            'documents/callback.txt',
            function (string $temporaryFile) use (&$callbackTemporary): string {
                $callbackTemporary = $temporaryFile;

                $this->assertFileExists($temporaryFile, 'withTemporaryFile should pass an existing temporary file into the callback.');

                return (string) file_get_contents($temporaryFile);
            },
            'invalid/extension',
            $this->temporaryDirectory,
            'callback_',
        );

        $this->assertSame('callback contents', $result, 'withTemporaryFile should return the callback result.');
        $this->assertIsString($callbackTemporary, 'Callback should receive the temporary file path.');
        $this->assertFileDoesNotExist($callbackTemporary, 'withTemporaryFile should delete the temporary file after callback completion.');
        $this->assertStringNotContainsString('invalid', basename($callbackTemporary), 'Invalid extension fragments should be ignored.');
    }

    public function testWithTemporaryFileDeletesTemporaryFileWhenCallbackThrows(): void
    {
        $disk              = $this->createDiskWithReadStream('throwing callback contents');
        $callbackTemporary = null;
        $shouldThrow       = microtime(true) > 0;

        try {
            TemporaryStorageFile::withTemporaryFile(
                $disk,
                'documents/throwing.txt',
                static function (string $temporaryFile) use (&$callbackTemporary, $shouldThrow): void {
                    $callbackTemporary = $temporaryFile;

                    if ($shouldThrow) {
                        throw new RuntimeException('callback failed');
                    }
                },
                null,
                $this->temporaryDirectory,
                'throwing_',
            );
        } catch (RuntimeException $exception) {
            $this->assertSame('callback failed', $exception->getMessage(), 'withTemporaryFile should rethrow callback exceptions.');
            $this->assertIsString($callbackTemporary, 'Callback should receive the temporary file path before throwing.');
            $this->assertFileDoesNotExist($callbackTemporary, 'withTemporaryFile should delete the temporary file when the callback throws.');

            return;
        }

        $this->fail('Expected callback runtime exception.');
    }

    public function testCopyFromStorageRejectsMissingStorageFile(): void
    {
        $disk = $this->createMock(StorageDiskInterface::class);
        $disk->method('name')->willReturn('remote');
        $disk->expects($this->once())
            ->method('fileExists')
            ->with('missing.txt')
            ->willReturn(false);
        $disk->expects($this->never())->method('readStream');

        try {
            TemporaryStorageFile::copyFromStorage($disk, 'missing.txt', null, $this->temporaryDirectory);
        } catch (FileException $exception) {
            $this->assertSame(404, $exception->getCode(), 'Missing storage files should be reported as not found.');
            $this->assertSame('File not found', $exception->getMessage(), 'Missing storage files should use the file-not-found exception.');

            return;
        }

        $this->fail('Expected missing storage file exception.');
    }

    public function testCopyFromStorageRejectsInvalidTemporaryDirectory(): void
    {
        $disk = $this->createExistingFileDisk();

        try {
            TemporaryStorageFile::copyFromStorage(
                $disk,
                'documents/report.txt',
                null,
                $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'missing',
            );
        } catch (FileException $exception) {
            $this->assertSame(400, $exception->getCode(), 'Invalid temporary directories should be rejected before reading storage.');
            $this->assertSame('Invalid file provided', $exception->getMessage(), 'Invalid temporary directories should use the invalid file exception.');

            return;
        }

        $this->fail('Expected invalid temporary directory exception.');
    }

    public function testCopyFromStorageRejectsNonResourceReadStreamAndRemovesTemporaryFile(): void
    {
        $disk = $this->createStub(StorageDiskInterface::class);
        $disk->method('name')->willReturn('remote');
        $disk->method('fileExists')->willReturn(true);
        $disk->method('readStream')->willReturn(false);

        try {
            TemporaryStorageFile::copyFromStorage($disk, 'documents/non-resource.txt', null, $this->temporaryDirectory, 'non_resource_');
        } catch (FileException $exception) {
            $this->assertSame(500, $exception->getCode(), 'Non-resource storage streams should fail as copy errors.');
            $this->assertSame('Cannot copy file', $exception->getMessage(), 'Non-resource storage streams should use the copy exception.');
            $this->assertSame([], $this->temporaryFiles('non_resource_'), 'Failed copies should remove the temporary file created before reading the stream.');

            return;
        }

        $this->fail('Expected non-resource stream copy exception.');
    }

    public function testCopyFromStorageWrapsStorageReadExceptionsAndRemovesTemporaryFile(): void
    {
        $disk = $this->createStub(StorageDiskInterface::class);
        $disk->method('name')->willReturn('remote');
        $disk->method('fileExists')->willReturn(true);
        $disk->method('readStream')->willThrowException(new RuntimeException('adapter failed'));

        try {
            TemporaryStorageFile::copyFromStorage($disk, 'documents/read-failure.txt', null, $this->temporaryDirectory, 'read_failure_');
        } catch (FileException $exception) {
            $this->assertSame(500, $exception->getCode(), 'Storage read exceptions should be wrapped as copy errors.');
            $this->assertSame('Cannot copy file', $exception->getMessage(), 'Storage read exceptions should use the copy exception.');
            $this->assertSame([], $this->temporaryFiles('read_failure_'), 'Failed reads should remove the temporary file created before storage read.');

            return;
        }

        $this->fail('Expected wrapped storage read exception.');
    }

    public function testCopyFromStorageRejectsTargetOpenFailureAndRemovesTemporaryFile(): void
    {
        $disk                                                 = $this->createDiskWithReadStream('target failure');
        TemporaryStorageFileFunctionOverrides::$failNextFopen = true;

        try {
            TemporaryStorageFile::copyFromStorage($disk, 'documents/target-open-failure.txt', null, $this->temporaryDirectory, 'target_open_failure_');
        } catch (FileException $exception) {
            $this->assertSame(500, $exception->getCode(), 'Target open failures should fail as copy errors.');
            $this->assertSame('Cannot copy file', $exception->getMessage(), 'Target open failures should use the copy exception.');
            $this->assertSame([], $this->temporaryFiles('target_open_failure_'), 'Failed target opens should remove the temporary file.');

            return;
        } finally {
            TemporaryStorageFileFunctionOverrides::reset();
        }

        $this->fail('Expected target open failure exception.');
    }

    public function testCopyFromStorageRejectsStreamCopyFailureAndRemovesTemporaryFile(): void
    {
        $registered = false;
        if (! in_array(FailingReadStreamWrapper::SCHEME, stream_get_wrappers(), true)) {
            $registered = stream_wrapper_register(FailingReadStreamWrapper::SCHEME, FailingReadStreamWrapper::class);
        }

        $stream = fopen(FailingReadStreamWrapper::path('source.txt'), 'rb');
        $this->assertIsResource($stream);

        $disk = $this->createStub(StorageDiskInterface::class);
        $disk->method('name')->willReturn('remote');
        $disk->method('fileExists')->willReturn(true);
        $disk->method('readStream')->willReturn($stream);

        try {
            TemporaryStorageFile::copyFromStorage($disk, 'documents/copy-failure.txt', null, $this->temporaryDirectory, 'copy_failure_');
        } catch (FileException $exception) {
            $this->assertSame(500, $exception->getCode(), 'Stream copy failures should fail as copy errors.');
            $this->assertSame('Cannot copy file', $exception->getMessage(), 'Stream copy failures should use the copy exception.');
            $this->assertSame([], $this->temporaryFiles('copy_failure_'), 'Failed stream copies should remove the temporary file.');

            return;
        } finally {
            if ($registered) {
                stream_wrapper_unregister(FailingReadStreamWrapper::SCHEME);
            }
        }

        $this->fail('Expected stream copy failure exception.');
    }

    public function testCopyFromStorageRejectsTemporaryNameCreationFailure(): void
    {
        $disk                                                   = $this->createExistingFileDisk();
        TemporaryStorageFileFunctionOverrides::$failNextTempnam = true;

        try {
            TemporaryStorageFile::copyFromStorage($disk, 'documents/report.txt', null, $this->temporaryDirectory, 'tempnam_failure_');
        } catch (FileException $exception) {
            $this->assertSame(400, $exception->getCode(), 'Temporary name creation failures should be reported as invalid file errors.');
            $this->assertSame('Invalid file provided', $exception->getMessage(), 'Temporary name creation failures should use the invalid file exception.');
            $this->assertSame([], $this->temporaryFiles('tempnam_failure_'), 'Failed temporary name creation should not leave files behind.');

            return;
        } finally {
            TemporaryStorageFileFunctionOverrides::reset();
        }

        $this->fail('Expected temporary name creation failure exception.');
    }

    public function testCopyFromStorageRejectsTemporaryFileRenameFailure(): void
    {
        $disk                   = $this->createExistingFileDisk();
        $previousErrorReporting = error_reporting();

        error_reporting($previousErrorReporting & ~E_WARNING);

        try {
            TemporaryStorageFile::copyFromStorage(
                $disk,
                'documents/report.txt',
                str_repeat('x', 4096),
                $this->temporaryDirectory,
                'rename_failure_',
            );
        } catch (FileException $exception) {
            $this->assertSame(500, $exception->getCode(), 'Temporary path rename failures should be reported as move errors.');
            $this->assertSame('Cannot move file', $exception->getMessage(), 'Temporary path rename failures should use the move exception.');
            $this->assertSame([], $this->temporaryFiles('rename_failure_'), 'Failed renames should remove the original tempnam file.');

            return;
        } finally {
            error_reporting($previousErrorReporting);
        }

        $this->fail('Expected temporary file rename failure exception.');
    }

    private function createExistingFileDisk(): StorageDiskInterface
    {
        $disk = $this->createMock(StorageDiskInterface::class);
        $disk->method('fileExists')->willReturn(true);
        $disk->expects($this->never())->method('readStream');

        return $disk;
    }

    private function createDiskWithReadStream(string $contents): StorageDiskInterface
    {
        $stream = fopen('php://temp', 'rb+');
        $this->assertIsResource($stream);
        fwrite($stream, $contents);
        rewind($stream);

        $disk = $this->createStub(StorageDiskInterface::class);
        $disk->method('fileExists')->willReturn(true);
        $disk->method('readStream')->willReturn($stream);

        return $disk;
    }

    /**
     * @return list<string>
     */
    private function temporaryFiles(string $prefix): array
    {
        $files = glob($this->temporaryDirectory . DIRECTORY_SEPARATOR . $prefix . '*');
        $this->assertIsArray($files);

        return $files;
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
