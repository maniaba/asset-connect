<?php

declare(strict_types=1);

namespace Tests\Pending;

use CodeIgniter\Config\Factories;
use CodeIgniter\Files\File;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;
use Maniaba\AssetConnect\Config\Asset as AssetConfig;
use Maniaba\AssetConnect\Enums\AssetVisibility;
use Maniaba\AssetConnect\Exceptions\PendingAssetException;
use Maniaba\AssetConnect\Pending\DefaultPendingStorage;
use Maniaba\AssetConnect\Pending\PendingAsset;
use Maniaba\AssetConnect\Storage\Interfaces\StorageDiskInterface;
use PHPUnit\Framework\MockObject\Stub;
use RuntimeException;
use Tests\Support\Config\TestAssetConfig;
use Tests\Support\Pending\PendingAssetManagerFunctionOverrides;

/**
 * @internal
 */
final class DefaultPendingStorageTest extends CIUnitTestCase
{
    private DefaultPendingStorage $storage;
    private string $tempFilePath;
    private string $basePendingPath;
    private string $storageRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storageRoot = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . 'asset-connect-pending-storage-test-' . bin2hex(random_bytes(4));

        $config           = new TestAssetConfig();
        $config->storages = [
            'protected' => [
                'driver'     => 'local',
                'root'       => $this->storageRoot,
                'visibility' => 'protected',
            ],
        ];

        Factories::injectMock('config', AssetConfig::class, $config);
        Factories::injectMock('config', 'Asset', $config);

        $this->storage = new DefaultPendingStorage();
        // Create a temporary file for testing
        $this->tempFilePath = tempnam(sys_get_temp_dir(), 'test_storage_');
        file_put_contents($this->tempFilePath, 'test storage content');
        // Set up base pending path
        $this->basePendingPath = $this->storageRoot . DIRECTORY_SEPARATOR . 'assets_pending' . DIRECTORY_SEPARATOR;
        // Ensure the base directory exists
        if (! is_dir($this->basePendingPath)) {
            mkdir($this->basePendingPath, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Clean up temporary file
        if (file_exists($this->tempFilePath)) {
            unlink($this->tempFilePath);
        }
        // Clean up pending storage directory
        if (is_dir($this->basePendingPath)) {
            helper('filesystem');
            delete_files($this->basePendingPath, true, true, true);
            @rmdir($this->basePendingPath);
        }

        if (is_dir($this->storageRoot)) {
            helper('filesystem');
            delete_files($this->storageRoot, true, true, true);
            @rmdir($this->storageRoot);
        }

        PendingAssetManagerFunctionOverrides::reset();
        Factories::reset('config');
    }

    /**
     * Test DefaultPendingStorage can be instantiated
     */
    public function testCanBeInstantiated(): void
    {
        // Act
        $storage = new DefaultPendingStorage();

        // Assert
        $this->assertInstanceOf(DefaultPendingStorage::class, $storage);
    }

    public function testUsesDefaultProtectedStorageWhenPendingStorageDiskIsNotConfigured(): void
    {
        $pendingAsset = PendingAsset::createFromFile($this->tempFilePath);

        $this->storage->store($pendingAsset, 'fallback-protected-id');

        $this->assertFileExists($this->basePendingPath . 'fallback-protected-id' . DIRECTORY_SEPARATOR . 'file');
    }

    public function testRejectsPublicPendingStorageDisk(): void
    {
        $config                     = new TestAssetConfig();
        $config->pendingStorageDisk = 'public';
        $config->storages           = [
            'public' => [
                'driver'     => 'local',
                'root'       => $this->storageRoot,
                'visibility' => 'public',
            ],
        ];

        Factories::injectMock('config', AssetConfig::class, $config);
        Factories::injectMock('config', 'Asset', $config);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Pending storage disk must be protected.');

        new DefaultPendingStorage();
    }

    /**
     * Test generatePendingId returns unique string
     */
    public function testGeneratePendingIdReturnsUniqueString(): void
    {
        // Act
        $id = $this->storage->generatePendingId();

        // Assert
        $this->assertIsString($id);
        $this->assertSame(32, strlen($id)); // 16 bytes = 32 hex characters
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $id);
    }

    /**
     * Test generatePendingId generates different IDs
     */
    public function testGeneratePendingIdGeneratesDifferentIds(): void
    {
        // Act
        $id1 = $this->storage->generatePendingId();
        $id2 = $this->storage->generatePendingId();
        $id3 = $this->storage->generatePendingId();

        // Assert
        $this->assertNotSame($id1, $id2);
        $this->assertNotSame($id2, $id3);
        $this->assertNotSame($id1, $id3);
    }

    public function testGeneratePendingIdAvoidsCollision(): void
    {
        $existingId  = bin2hex(random_bytes(16));
        $existingDir = $this->basePendingPath . $existingId . DIRECTORY_SEPARATOR;
        mkdir($existingDir, 0755, true);
        file_put_contents($existingDir . 'file', 'existing content');

        $newId = $this->storage->generatePendingId();

        $this->assertNotSame($existingId, $newId);

        @unlink($existingDir . 'file');
        rmdir($existingDir);
    }

    public function testGeneratePendingIdRetriesWhenGeneratedIdAlreadyExists(): void
    {
        PendingAssetManagerFunctionOverrides::$randomBytesQueue = [
            str_repeat("\x01", 16),
            str_repeat("\x02", 16),
        ];

        $disk = $this->protectedDisk();
        $disk->method('fileExists')->willReturnOnConsecutiveCalls(true, false, false);

        $storage = new DefaultPendingStorage($disk, 'pending');

        $this->assertSame(str_repeat('02', 16), $storage->generatePendingId(), 'Pending ID generation should retry when the first generated ID already exists.');
    }

    public function testGeneratePendingIdThrowsAfterCollisionRetryLimit(): void
    {
        PendingAssetManagerFunctionOverrides::$randomBytesQueue = [
            str_repeat("\x01", 16),
            str_repeat("\x02", 16),
            str_repeat("\x03", 16),
            str_repeat("\x04", 16),
            str_repeat("\x05", 16),
            str_repeat("\x06", 16),
        ];

        $disk = $this->protectedDisk();
        $disk->method('fileExists')->willReturn(true);

        $storage = new DefaultPendingStorage($disk, 'pending');

        $this->expectException(PendingAssetException::class);
        $this->expectExceptionMessage('Unable to generate unique pending ID after 5 attempts.');

        $storage->generatePendingId();
    }

    /**
     * Test getDefaultTTLSeconds returns correct value
     */
    public function testGetDefaultTTLSecondsReturnsCorrectValue(): void
    {
        // Act
        $ttl = $this->storage->getDefaultTTLSeconds();

        // Assert
        $this->assertSame(86400, $ttl); // 24 hours
    }

    /**
     * Test store creates pending asset files
     */
    public function testStoreCreatesPendingAssetFiles(): void
    {
        // Arrange
        $pendingAsset = PendingAsset::createFromFile($this->tempFilePath);
        $id           = 'test-store-id';

        // Act
        $this->storage->store($pendingAsset, $id);

        // Assert
        $filePath     = $this->basePendingPath . $id . DIRECTORY_SEPARATOR . 'file';
        $metadataPath = $this->basePendingPath . $id . DIRECTORY_SEPARATOR . 'metadata.json';

        $this->assertFileExists($filePath);
        $this->assertFileExists($metadataPath);

        // Verify file content
        $this->assertSame('test storage content', file_get_contents($filePath));

        // Verify metadata
        $metadata = json_decode(file_get_contents($metadataPath), true);
        $this->assertIsArray($metadata);
        $this->assertArrayHasKey('id', $metadata);
        $this->assertArrayHasKey('name', $metadata);
        $this->assertArrayHasKey('file_name', $metadata);
    }

    /**
     * Test store generates ID when not provided
     */
    public function testStoreGeneratesIdWhenNotProvided(): void
    {
        // Arrange
        $pendingAsset = PendingAsset::createFromFile($this->tempFilePath);

        // Act
        $this->storage->store($pendingAsset);

        // Assert
        $this->assertNotEmpty($pendingAsset->id);
        $this->assertSame(32, strlen($pendingAsset->id));
    }

    public function testStoreCreatesPendingPrefixIfNotExists(): void
    {
        // Arrange
        $pendingAsset = PendingAsset::createFromFile($this->tempFilePath);
        $id           = 'new-directory-id';
        $expectedDir  = $this->basePendingPath . $id . DIRECTORY_SEPARATOR;

        $this->assertDirectoryDoesNotExist($expectedDir);

        // Act
        $this->storage->store($pendingAsset, $id);

        // Assert
        $this->assertDirectoryExists($expectedDir);
    }

    /**
     * Test store throws exception when unable to write metadata file
     */
    public function testStoreThrowsExceptionWhenUnableToWriteMetadata(): void
    {
        // Arrange
        $pendingAsset = PendingAsset::createFromFile($this->tempFilePath);
        $id           = 'fail-metadata-id';

        // Create the directory and file first
        $targetDir = $this->basePendingPath . $id . DIRECTORY_SEPARATOR;
        mkdir($targetDir, 0755, true);

        // Create a directory where metadata.json should be to prevent file creation
        $metadataPath = $targetDir . 'metadata.json';
        mkdir($metadataPath, 0755, true);

        // Act & Assert
        $this->expectException(PendingAssetException::class);
        $this->expectExceptionMessage('unable_to_store_pending_asset');

        try {
            $this->storage->store($pendingAsset, $id);
        } finally {
            // Cleanup - remove directory
            @rmdir($metadataPath);
        }
    }

    /**
     * Test fetchById returns pending asset when exists
     */
    public function testFetchByIdReturnsPendingAssetWhenExists(): void
    {
        // Arrange
        $pendingAsset = PendingAsset::createFromFile($this->tempFilePath);
        $pendingAsset->usingName('fetch-test-asset');
        $id = 'fetch-test-id';

        $this->storage->store($pendingAsset, $id);

        // Act
        $result = $this->storage->fetchById($id);

        // Assert
        $this->assertInstanceOf(PendingAsset::class, $result);
        $this->assertSame('fetch-test-asset', $result->name);
    }

    /**
     * Test fetchById returns null when asset does not exist
     */
    public function testFetchByIdReturnsNullWhenAssetDoesNotExist(): void
    {
        // Act
        $result = $this->storage->fetchById('non-existent-id');

        // Assert
        $this->assertNotInstanceOf(PendingAsset::class, $result);
    }

    public function testFetchByIdRejectsInvalidPendingId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Pending asset ID contains invalid characters.');

        $this->storage->fetchById('../outside');
    }

    /**
     * Test fetchById returns null when file exists but metadata missing
     */
    public function testFetchByIdReturnsNullWhenMetadataMissing(): void
    {
        // Arrange
        $id  = 'missing-metadata-id';
        $dir = $this->basePendingPath . $id . DIRECTORY_SEPARATOR;
        mkdir($dir, 0755, true);
        file_put_contents($dir . 'file', 'content');
        // Don't create metadata.json

        // Act
        $result = $this->storage->fetchById($id);

        // Assert
        $this->assertNotInstanceOf(PendingAsset::class, $result);
    }

    /**
     * Test fetchById returns null when metadata exists but file missing
     */
    public function testFetchByIdReturnsNullWhenFileMissing(): void
    {
        // Arrange
        $id  = 'missing-file-id';
        $dir = $this->basePendingPath . $id . DIRECTORY_SEPARATOR;
        mkdir($dir, 0755, true);
        file_put_contents($dir . 'metadata.json', '{}');
        // Don't create file

        // Act
        $result = $this->storage->fetchById($id);

        // Assert
        $this->assertNotInstanceOf(PendingAsset::class, $result);
    }

    /**
     * Test fetchById throws exception when metadata contains invalid JSON
     */
    public function testFetchByIdThrowsExceptionWhenInvalidJson(): void
    {
        // Arrange
        $id  = 'invalid-json-id';
        $dir = $this->basePendingPath . $id . DIRECTORY_SEPARATOR;
        mkdir($dir, 0755, true);
        file_put_contents($dir . 'file', 'content');
        file_put_contents($dir . 'metadata.json', '{invalid json}');

        // Act & Assert
        $this->expectException(PendingAssetException::class);
        $this->storage->fetchById($id);
    }

    public function testFetchByIdThrowsWhenMetadataCannotBeRead(): void
    {
        $disk = $this->protectedDisk();
        $disk->method('fileExists')->willReturn(true);
        $disk->method('read')->willThrowException(new RuntimeException('metadata read failed'));

        $storage = new DefaultPendingStorage($disk, 'pending');

        $this->expectException(PendingAssetException::class);

        $storage->fetchById('metadata-read-fails');
    }

    public function testFetchByIdThrowsWhenPendingFileStreamIsNotReadable(): void
    {
        $disk = $this->protectedDisk();
        $disk->method('fileExists')->willReturn(true);
        $disk->method('read')->willReturn('{"name":"stream-fail"}');
        $disk->method('readStream')->willReturn(false);

        $storage = new DefaultPendingStorage($disk, 'pending');

        $this->expectException(PendingAssetException::class);

        $storage->fetchById('stream-read-fails');
    }

    public function testFetchByIdWrapsUnexpectedPendingFileStreamErrors(): void
    {
        $disk = $this->protectedDisk();
        $disk->method('fileExists')->willReturn(true);
        $disk->method('read')->willReturn('{"name":"stream-exception"}');
        $disk->method('readStream')->willThrowException(new RuntimeException('adapter read stream failed'));

        $storage = new DefaultPendingStorage($disk, 'pending');

        $this->expectException(PendingAssetException::class);

        $storage->fetchById('stream-exception-id');
    }

    public function testFetchByIdThrowsWhenTemporaryFileCannotBeCreated(): void
    {
        PendingAssetManagerFunctionOverrides::$failNextTempnam = true;

        $disk = $this->protectedDisk();
        $disk->method('fileExists')->willReturn(true);
        $disk->method('read')->willReturn('{"name":"tempnam-fail"}');

        $storage = new DefaultPendingStorage($disk, 'pending');

        $this->expectException(PendingAssetException::class);

        try {
            $storage->fetchById('tempnam-fails');
        } finally {
            PendingAssetManagerFunctionOverrides::reset();
        }
    }

    public function testFetchByIdWrapsPendingAssetCreationFailure(): void
    {
        PendingAssetManagerFunctionOverrides::$deleteStreamCopyTarget = true;

        $disk = $this->protectedDisk();
        $disk->method('fileExists')->willReturn(true);
        $disk->method('read')->willReturn('{"name":"creation-fails"}');
        $disk->method('readStream')->willReturnCallback(static function () {
            $stream = fopen('php://temp', 'rb+');
            fwrite($stream, 'pending content');
            rewind($stream);

            return $stream;
        });

        $storage = new DefaultPendingStorage($disk, 'pending');

        $this->expectException(PendingAssetException::class);

        try {
            $storage->fetchById('creation-fails-id');
        } finally {
            PendingAssetManagerFunctionOverrides::reset();
        }
    }

    public function testDeleteByIdRemovesPendingAssetFiles(): void
    {
        $pendingAsset = PendingAsset::createFromFile($this->tempFilePath);
        $id           = 'delete-test-id';

        $this->storage->store($pendingAsset, $id);

        $dir = $this->basePendingPath . $id . DIRECTORY_SEPARATOR;
        $this->assertDirectoryExists($dir);

        $result = $this->storage->deleteById($id);

        $this->assertTrue($result);
        $this->assertFileDoesNotExist($dir . 'file');
        $this->assertFileDoesNotExist($dir . 'metadata.json');
    }

    public function testDeleteByIdReturnsTrueWhenPendingFilesDoNotExist(): void
    {
        // Act
        $result = $this->storage->deleteById('non-existent-id');

        // Assert
        $this->assertTrue($result);
    }

    public function testDeleteByIdRemovesKnownPendingFiles(): void
    {
        $id           = 'recursive-delete-id';
        $pendingAsset = PendingAsset::createFromFile($this->tempFilePath);

        $this->storage->store($pendingAsset, $id);

        $dir = $this->basePendingPath . $id . DIRECTORY_SEPARATOR;
        mkdir($dir . 'variants', 0755, true);
        file_put_contents($dir . 'variants/thumb.txt', 'thumb');

        $result = $this->storage->deleteById($id);

        $this->assertTrue($result);
        $this->assertFileDoesNotExist($dir . 'file');
        $this->assertFileDoesNotExist($dir . 'metadata.json');
        $this->assertFileExists($dir . 'variants/thumb.txt');
    }

    public function testDeleteByIdReturnsFalseWhenStorageDeleteFails(): void
    {
        $disk = $this->protectedDisk();
        $disk->method('fileExists')->willReturn(true);
        $disk->method('delete')->willThrowException(new RuntimeException('delete failed'));

        $storage = new DefaultPendingStorage($disk, 'pending');

        $this->assertFalse($storage->deleteById('delete-fails'), 'Delete should report false when storage delete fails.');
    }

    /**
     * Test full lifecycle: store, fetch, delete
     */
    public function testFullLifecycleStoreFetchDelete(): void
    {
        // Arrange
        $pendingAsset = PendingAsset::createFromFile($this->tempFilePath);
        $pendingAsset->usingName('lifecycle-test')
            ->setOrder(5)
            ->withCustomProperty('test', 'value');

        $id = 'lifecycle-id';

        // Act & Assert - Store
        $this->storage->store($pendingAsset, $id);
        $this->assertSame($id, $pendingAsset->id);

        // Act & Assert - Fetch
        $fetchedAsset = $this->storage->fetchById($id);
        $this->assertInstanceOf(PendingAsset::class, $fetchedAsset);
        $this->assertSame('lifecycle-test', $fetchedAsset->name);
        $this->assertSame(5, $fetchedAsset->order);
        $this->assertSame('value', $fetchedAsset->custom_properties['test']);

        // Act & Assert - Delete
        $result = $this->storage->deleteById($id);
        $this->assertTrue($result);

        // Verify deleted
        $fetchedAfterDelete = $this->storage->fetchById($id);
        $this->assertNotInstanceOf(PendingAsset::class, $fetchedAfterDelete);
    }

    /**
     * Test store overwrites existing asset with same ID
     */
    public function testStoreOverwritesExistingAssetWithSameId(): void
    {
        // Arrange
        $id = 'overwrite-id';

        // Create separate temp files for each asset
        $tempFile1 = tempnam(sys_get_temp_dir(), 'test_storage_1_');
        $tempFile2 = tempnam(sys_get_temp_dir(), 'test_storage_2_');
        file_put_contents($tempFile1, 'first content');
        file_put_contents($tempFile2, 'second content');

        $asset1 = PendingAsset::createFromFile($tempFile1);
        $asset1->usingName('first-asset');
        $this->storage->store($asset1, $id);

        $asset2 = PendingAsset::createFromFile($tempFile2);
        $asset2->usingName('second-asset');

        // Act
        $this->storage->store($asset2, $id);

        // Assert
        $fetchedAsset = $this->storage->fetchById($id);
        $this->assertSame('second-asset', $fetchedAsset->name);

        // Cleanup
        @unlink($tempFile1);
        @unlink($tempFile2);
    }

    public function testStoreRejectsUnreadableSourceFile(): void
    {
        $pendingAsset = PendingAsset::createFromFile($this->tempFilePath);

        $file = $this->createStub(File::class);
        $file->method('getRealPath')->willReturn($this->storageRoot . DIRECTORY_SEPARATOR . 'missing-source.txt');

        $pendingAsset->setFile($file);

        $this->expectException(PendingAssetException::class);
        $this->expectExceptionMessage('unable_to_store_pending_asset');

        $this->storage->store($pendingAsset, 'missing-source-id');
    }

    public function testStoreThrowsWhenMetadataCannotBeEncoded(): void
    {
        $pendingAsset = PendingAsset::createFromFile($this->tempFilePath);
        $resource     = fopen('php://temp', 'rb');
        $this->assertIsResource($resource);

        $pendingAsset->withCustomProperty('resource', $resource);

        $this->expectException(PendingAssetException::class);
        $this->expectExceptionMessage('unable_to_store_pending_asset');

        try {
            $this->storage->store($pendingAsset, 'metadata-encode-fails');
        } finally {
            fclose($resource);
        }
    }

    public function testStoreThrowsWhenSourceStreamCannotBeOpened(): void
    {
        PendingAssetManagerFunctionOverrides::$failNextFopen = true;

        $pendingAsset = PendingAsset::createFromFile($this->tempFilePath);

        $this->expectException(PendingAssetException::class);
        $this->expectExceptionMessage('unable_to_store_pending_asset');

        try {
            $this->storage->store($pendingAsset, 'fopen-fails');
        } finally {
            PendingAssetManagerFunctionOverrides::reset();
        }
    }

    public function testStoreThrowsWhenStorageStreamWriteFails(): void
    {
        $disk = $this->protectedDisk();
        $disk->method('fileExists')->willReturn(false);
        $disk->method('writeStream')->willThrowException(new RuntimeException('adapter write failed'));

        $storage      = new DefaultPendingStorage($disk, 'pending');
        $pendingAsset = PendingAsset::createFromFile($this->tempFilePath);

        $this->expectException(PendingAssetException::class);
        $this->expectExceptionMessage('unable_to_store_pending_asset');

        $storage->store($pendingAsset, 'write-stream-fails');
    }

    public function testConstructorRejectsEmptyPendingStoragePrefix(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Pending storage prefix must not be empty.');

        new DefaultPendingStorage($this->protectedDisk(), '///');
    }

    public function testNormalizeMetadataHandlesTimeObjectsStringsAndInvalidDates(): void
    {
        $normalizeMetadata = $this->getPrivateMethodInvoker($this->storage, 'normalizeMetadata');

        $createdAt = Time::parse('2026-01-02 03:04:05');
        $metadata  = $normalizeMetadata([
            'created_at' => $createdAt,
            'updated_at' => ['date' => '2026-02-03 04:05:06.000000'],
        ]);

        $this->assertInstanceOf(Time::class, $metadata['created_at'], 'Time metadata values should remain normalized as Time instances.');
        $this->assertSame($createdAt->getTimestamp(), $metadata['created_at']->getTimestamp());
        $this->assertInstanceOf(Time::class, $metadata['updated_at'], 'Array date metadata values should be parsed into Time instances.');

        $metadata = $normalizeMetadata([
            'created_at' => 'not a date',
            'updated_at' => '',
        ]);

        $this->assertSame('not a date', $metadata['created_at'], 'Invalid date strings should be kept unchanged.');
        $this->assertSame('', $metadata['updated_at'], 'Empty date strings should be kept unchanged.');
    }

    /**
     * Test update scenario - metadata is updated but file remains the same
     */
    public function testUpdateExistingAssetUpdatesMetadataButKeepsFile(): void
    {
        // Arrange - Create and store initial asset
        $id          = 'update-test-id';
        $initialName = 'initial-name';
        $updatedName = 'updated-name';

        $tempFile1 = tempnam(sys_get_temp_dir(), 'update_test_1_');
        file_put_contents($tempFile1, 'original file content for update test');

        $initialAsset = PendingAsset::createFromFile($tempFile1);
        $initialAsset->usingName($initialName)
            ->withCustomProperty('version', 1)
            ->setOrder(1);

        // Store initial asset (tempFile1 will be deleted by store)
        $this->storage->store($initialAsset, $id);

        // Get file path and stats
        $storedFilePath = $this->basePendingPath . $id . DIRECTORY_SEPARATOR . 'file';
        $this->assertFileExists($storedFilePath);
        $originalFileContent = file_get_contents($storedFilePath);
        $originalFileModTime = filemtime($storedFilePath);
        $originalFileInode   = fileinode($storedFilePath);

        // Small delay to ensure different modification time if file was touched
        usleep(100000); // 0.1 second

        // Act - Create NEW temp file for update (since tempFile1 was deleted)
        $tempFile2 = tempnam(sys_get_temp_dir(), 'update_test_2_');
        file_put_contents($tempFile2, 'this content should NOT be used on update');

        $updatedAsset = PendingAsset::createFromFile($tempFile2);
        $updatedAsset->usingName($updatedName)
            ->withCustomProperty('version', 2)
            ->withCustomProperty('status', 'modified')
            ->setOrder(5);

        $this->storage->store($updatedAsset, $id); // Same ID = update

        // Assert - File should remain unchanged
        $this->assertFileExists($storedFilePath, 'File should still exist');

        $newFileContent = file_get_contents($storedFilePath);
        $newFileModTime = filemtime($storedFilePath);
        $newFileInode   = fileinode($storedFilePath);

        // File content should be exactly the same as original (NOT tempFile2 content)
        $this->assertSame($originalFileContent, $newFileContent, 'File content should not change on update');
        $this->assertNotSame('this content should NOT be used on update', $newFileContent, 'New file content should not be used');

        // File should not have been modified (same modification time)
        $this->assertSame($originalFileModTime, $newFileModTime, 'File modification time should not change');

        // File inode should be the same (same physical file)
        $this->assertSame($originalFileInode, $newFileInode, 'File inode should be the same (not replaced)');

        // Metadata should be updated
        $fetchedAsset = $this->storage->fetchById($id);
        $this->assertInstanceOf(PendingAsset::class, $fetchedAsset, 'Asset should be fetchable');
        $this->assertSame($updatedName, $fetchedAsset->name, 'Name should be updated');
        $this->assertSame(2, $fetchedAsset->custom_properties['version'], 'Custom property version should be updated');
        $this->assertSame('modified', $fetchedAsset->custom_properties['status'], 'New custom property should be added');
        $this->assertSame(5, $fetchedAsset->order, 'Order should be updated');

        // Note: temp files are already deleted by storage->store()
        $this->assertFileDoesNotExist($tempFile1, 'Initial temp file should be deleted');
        $this->assertFileDoesNotExist($tempFile2, 'Updated temp file should be deleted');
    }

    /**
     * Test that storing with same ID multiple times keeps updating metadata
     */
    public function testMultipleUpdatesKeepUpdatingMetadata(): void
    {
        // Arrange - Create initial asset
        $id              = 'multiple-updates-id';
        $originalContent = 'original file content';
        $tempFiles       = [];

        // Create and store initial asset
        $tempFiles[] = tempnam(sys_get_temp_dir(), 'multi_update_0_');
        file_put_contents($tempFiles[0], $originalContent);

        $initialAsset = PendingAsset::createFromFile($tempFiles[0]);
        $initialAsset->usingName('initial-name');
        $this->storage->store($initialAsset, $id);

        // Get original file stats
        $storedFilePath  = $this->basePendingPath . $id . DIRECTORY_SEPARATOR . 'file';
        $originalContent = file_get_contents($storedFilePath);
        $originalInode   = fileinode($storedFilePath);

        // Act & Assert - Update metadata multiple times
        for ($i = 1; $i <= 3; $i++) {
            // Create new temp file for each iteration (since previous one is deleted)
            $tempFiles[] = tempnam(sys_get_temp_dir(), "multi_update_{$i}_");
            file_put_contents($tempFiles[$i], "different content {$i}");

            $asset = PendingAsset::createFromFile($tempFiles[$i]);
            $asset->usingName("name-version-{$i}")
                ->withCustomProperty('iteration', $i)
                ->setOrder($i * 10);

            $this->storage->store($asset, $id);

            // Fetch and verify metadata was updated
            $fetched = $this->storage->fetchById($id);
            $this->assertSame("name-version-{$i}", $fetched->name);
            $this->assertSame($i, $fetched->custom_properties['iteration']);
            $this->assertSame($i * 10, $fetched->order);

            // Verify file content hasn't changed
            $currentContent = file_get_contents($storedFilePath);
            $this->assertSame($originalContent, $currentContent, "File content should remain unchanged after update {$i}");

            // Verify it's the same physical file
            $currentInode = fileinode($storedFilePath);
            $this->assertSame($originalInode, $currentInode, "File inode should remain the same after update {$i}");
        }
    }

    private function protectedDisk(): StorageDiskInterface&Stub
    {
        $disk = $this->createStub(StorageDiskInterface::class);
        $disk->method('visibility')->willReturn(AssetVisibility::PROTECTED);

        return $disk;
    }
}
