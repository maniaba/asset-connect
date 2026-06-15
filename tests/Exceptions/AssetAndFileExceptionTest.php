<?php

declare(strict_types=1);

namespace Tests\Exceptions;

use CodeIgniter\Test\CIUnitTestCase;
use Maniaba\AssetConnect\Exceptions\AssetException;
use Maniaba\AssetConnect\Exceptions\FileException;
use RuntimeException;
use Tests\Support\Entities\FakeAssetEntity;

/**
 * @internal
 */
final class AssetAndFileExceptionTest extends CIUnitTestCase
{
    public function testAssetExceptionNormalizesStringError(): void
    {
        $exception = new AssetException('single error', 'Custom message', 418);

        $this->assertSame(['single error'], $exception->errors);
        $this->assertSame('Custom message', $exception->getMessage());
        $this->assertSame(418, $exception->getCode());
    }

    public function testAssetExceptionFactoriesExposeErrorsAndStatusCodes(): void
    {
        $invalidEntity      = AssetException::forInvalidEntity(new FakeAssetEntity());
        $fileNameNotAllowed = AssetException::forFileNameNotAllowed('../secret.txt');
        $fileTooLarge       = AssetException::forFileTooLarge(2048, 1024);
        $invalidExtension   = AssetException::forInvalidFileExtension('exe', ['txt', 'pdf']);
        $invalidMimeType    = AssetException::forInvalidMimeType('application/x-msdownload', ['text/plain']);
        $databaseError      = AssetException::forDatabaseError(['name' => 'Name is required.']);
        $pendingNotFound    = AssetException::forPendingAssetNotFound('missing-id');
        $missingDefinition  = AssetException::forMissingEntityKeyDefinition(FakeAssetEntity::class);

        $this->assertExceptionHasCodeAndErrors($invalidEntity, 400);
        $this->assertExceptionHasCodeAndErrors($fileNameNotAllowed, 400);
        $this->assertExceptionHasCodeAndErrors($fileTooLarge, 413);
        $this->assertExceptionHasCodeAndErrors($invalidExtension, 400);
        $this->assertExceptionHasCodeAndErrors($invalidMimeType, 400);
        $this->assertExceptionHasCodeAndErrors($databaseError, 500);
        $this->assertExceptionHasCodeAndErrors($pendingNotFound, 404);
        $this->assertExceptionHasCodeAndErrors($missingDefinition, 500);
        $this->assertSame(['name' => 'Name is required.'], $databaseError->errors);
    }

    public function testFileExceptionFactoriesExposeErrorsAndStatusCodes(): void
    {
        $invalidFile    = FileException::forInvalidFile('/tmp/invalid.txt');
        $fileNotFound   = FileException::forFileNotFound('/tmp/missing.txt');
        $cannotCopyFile = FileException::forCannotCopyFile('/tmp/source.txt', 'public:target.txt');
        $cannotMoveFile = FileException::forCannotMoveFile('/tmp/source.txt', 'public:target.txt');
        $storageError   = FileException::forCannotWriteToStorage(
            's3',
            'assets/source.txt',
            new RuntimeException('Access denied'),
        );

        $this->assertExceptionHasCodeAndErrors($invalidFile, 400);
        $this->assertExceptionHasCodeAndErrors($fileNotFound, 404);
        $this->assertExceptionHasCodeAndErrors($cannotCopyFile, 500);
        $this->assertExceptionHasCodeAndErrors($cannotMoveFile, 500);
        $this->assertExceptionHasCodeAndErrors($storageError, 500);
        $this->assertSame('Access denied', $storageError->getPrevious()?->getMessage());
        $this->assertStringContainsString('storage disk "s3"', (string) $storageError->errors[0]);
        $this->assertStringContainsString('assets/source.txt', (string) $storageError->errors[0]);
    }

    private function assertExceptionHasCodeAndErrors(AssetException $exception, int $code): void
    {
        $this->assertSame($code, $exception->getCode());
        $this->assertNotSame([], $exception->errors);
        $this->assertNotSame('', $exception->getMessage());
    }
}
