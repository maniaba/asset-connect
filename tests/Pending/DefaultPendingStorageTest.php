<?php

declare(strict_types=1);

namespace Tests\Pending;

use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use Maniaba\AssetConnect\Exceptions\PendingAssetException;
use Maniaba\AssetConnect\Pending\DefaultPendingStorage;
use Maniaba\AssetConnect\Pending\PendingAsset;
use Override;

/**
 * @internal
 */
final class DefaultPendingStorageTest extends CIUnitTestCase
{
    private DefaultPendingStorage $storage;
    private string $tempFilePath;
    private string $basePendingPath;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->storage = new DefaultPendingStorage();

        // Create a temporary file for testing
        $this->tempFilePath = tempnam(sys_get_temp_dir(), 'test_storage_');
        file_put_contents($this->tempFilePath, 'test storage content');

        // Set up base pending path
        $this->basePendingPath = WRITEPATH . 'assets_pending' . DIRECTORY_SEPARATOR;

        // Ensure the base directory exists
        if (! is_dir($this->basePendingPath)) {
            mkdir($this->basePendingPath, 0755, true);
        }
    }

    #[Override]
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

    /**
     * Test generatePendingId avoids collision when directory exists
     */
    public function testGeneratePendingIdAvoidsCollision(): void
    {
        // Arrange - Create a directory with a potential ID
        $existingId  = bin2hex(random_bytes(16));
        $existingDir = $this->basePendingPath . $existingId . DIRECTORY_SEPARATOR;
        mkdir($existingDir, 0755, true);

        // Act
        $newId = $this->storage->generatePendingId();

        // Assert
        $this->assertNotSame($existingId, $newId);

        // Cleanup
        rmdir($existingDir);
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

    /**
     * Test store creates directory if not exists
     */
    public function testStoreCreatesDirectoryIfNotExists(): void
    {
        // Arrange
        $pendingAsset = PendingAsset::createFromFile($this->tempFilePath);
        $id           = 'new-directory-id';
        $expectedDir  = $this->basePendingPath . $id . DIRECTORY_SEPARATOR;

        // Verify directory doesn't exist before
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

    /**
     * Test deleteById removes pending asset directory
     */
    public function testDeleteByIdRemovesPendingAssetDirectory(): void
    {
        // Arrange
        $pendingAsset = PendingAsset::createFromFile($this->tempFilePath);
        $id           = 'delete-test-id';

        $this->storage->store($pendingAsset, $id);

        $dir = $this->basePendingPath . $id . DIRECTORY_SEPARATOR;
        $this->assertDirectoryExists($dir);

        // Act
        $result = $this->storage->deleteById($id);

        // Assert
        $this->assertTrue($result);
        $this->assertDirectoryDoesNotExist($dir);
    }

    /**
     * Test deleteById returns true when directory does not exist
     */
    public function testDeleteByIdReturnsTrueWhenDirectoryDoesNotExist(): void
    {
        // Act
        $result = $this->storage->deleteById('non-existent-id');

        // Assert
        $this->assertTrue($result);
    }

    /**
     * Test deleteById removes all files in directory recursively
     */
    public function testDeleteByIdRemovesAllFilesRecursively(): void
    {
        // Arrange
        $id  = 'recursive-delete-id';
        $dir = $this->basePendingPath . $id . DIRECTORY_SEPARATOR;
        mkdir($dir . 'subdir', 0755, true);
        file_put_contents($dir . 'file1.txt', 'content1');
        file_put_contents($dir . 'subdir/file2.txt', 'content2');

        // Act
        $result = $this->storage->deleteById($id);

        // Assert
        $this->assertTrue($result);
        $this->assertDirectoryDoesNotExist($dir);
    }

    /**
     * Test cleanExpiredPendingAssets removes expired assets
     */
    public function testCleanExpiredPendingAssetsRemovesExpiredAssets(): void
    {
        // Arrange
        $expiredId = 'expired-asset-id';
        $validId   = 'valid-asset-id';

        // Create separate temp files for each asset
        $expiredTempFile = tempnam(sys_get_temp_dir(), 'test_expired_');
        $validTempFile   = tempnam(sys_get_temp_dir(), 'test_valid_');
        file_put_contents($expiredTempFile, 'expired content');
        file_put_contents($validTempFile, 'valid content');

        // Create expired asset (created 2 days ago)
        $expiredAsset = PendingAsset::createFromFile($expiredTempFile);
        $this->setPrivateProperty($expiredAsset, 'created_at', Time::now()->subDays(2));
        $this->storage->store($expiredAsset, $expiredId);

        // Create valid asset (created 1 hour ago)
        $validAsset = PendingAsset::createFromFile($validTempFile);
        $this->setPrivateProperty($validAsset, 'created_at', Time::now()->subHours(1));
        $this->storage->store($validAsset, $validId);

        // Update metadata files with correct timestamps
        $this->updateMetadataTimestamp($expiredId, Time::now()->subDays(2));
        $this->updateMetadataTimestamp($validId, Time::now()->subHours(1));

        // Act
        $this->storage->cleanExpiredPendingAssets();

        // Assert
        $expiredDir = $this->basePendingPath . $expiredId . DIRECTORY_SEPARATOR;
        $validDir   = $this->basePendingPath . $validId . DIRECTORY_SEPARATOR;

        $this->assertDirectoryDoesNotExist($expiredDir, 'Expired asset should be deleted');
        $this->assertDirectoryExists($validDir, 'Valid asset should still exist');

        // Cleanup temp files
        @unlink($expiredTempFile);
        @unlink($validTempFile);
    }

    /**
     * Test cleanExpiredPendingAssets does nothing when base directory does not exist
     */
    public function testCleanExpiredPendingAssetsDoesNothingWhenBaseDirNotExists(): void
    {
        // Arrange - Delete base directory
        delete_files($this->basePendingPath, true, true, true);
        rmdir($this->basePendingPath);

        // Act & Assert - Should not throw exception
        $this->storage->cleanExpiredPendingAssets();
        $this->assertTrue(true); // If we get here, no exception was thrown
    }

    /**
     * Test cleanExpiredPendingAssets handles empty directory
     */
    public function testCleanExpiredPendingAssetsHandlesEmptyDirectory(): void
    {
        // Act & Assert - Should not throw exception
        $this->storage->cleanExpiredPendingAssets();
        $this->assertTrue(true);
    }

    /**
     * Test cleanExpiredPendingAssets skips directory without metadata
     */
    public function testCleanExpiredPendingAssetsSkipsDirectoryWithoutMetadata(): void
    {
        // Arrange
        $id  = 'no-metadata-id';
        $dir = $this->basePendingPath . $id . DIRECTORY_SEPARATOR;
        mkdir($dir, 0755, true);
        file_put_contents($dir . 'file', 'content');
        // Don't create metadata.json

        // Act
        $this->storage->cleanExpiredPendingAssets();

        // Assert - Directory should still exist
        $this->assertDirectoryExists($dir);
    }

    /**
     * Test cleanExpiredPendingAssets skips invalid JSON metadata
     */
    public function testCleanExpiredPendingAssetsSkipsInvalidJsonMetadata(): void
    {
        // Arrange
        $id  = 'invalid-json-cleanup-id';
        $dir = $this->basePendingPath . $id . DIRECTORY_SEPARATOR;
        mkdir($dir, 0755, true);
        file_put_contents($dir . 'file', 'content');
        file_put_contents($dir . 'metadata.json', '{invalid json}');

        // Act
        $this->storage->cleanExpiredPendingAssets();

        // Assert - Directory should still exist
        $this->assertDirectoryExists($dir);
    }

    /**
     * Test cleanExpiredPendingAssets skips metadata without created_at
     */
    public function testCleanExpiredPendingAssetsSkipsMetadataWithoutCreatedAt(): void
    {
        // Arrange
        $id  = 'no-created-at-id';
        $dir = $this->basePendingPath . $id . DIRECTORY_SEPARATOR;
        mkdir($dir, 0755, true);
        file_put_contents($dir . 'file', 'content');
        file_put_contents($dir . 'metadata.json', json_encode(['name' => 'test']));

        // Act
        $this->storage->cleanExpiredPendingAssets();

        // Assert - Directory should still exist
        $this->assertDirectoryExists($dir);
    }

    /**
     * Test cleanExpiredPendingAssets skips invalid created_at format
     */
    public function testCleanExpiredPendingAssetsSkipsInvalidCreatedAtFormat(): void
    {
        // Arrange
        $id  = 'invalid-date-id';
        $dir = $this->basePendingPath . $id . DIRECTORY_SEPARATOR;
        mkdir($dir, 0755, true);
        file_put_contents($dir . 'file', 'content');
        file_put_contents($dir . 'metadata.json', json_encode(['created_at' => 'invalid-date']));

        // Act
        $this->storage->cleanExpiredPendingAssets();

        // Assert - Directory should still exist
        $this->assertDirectoryExists($dir);
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

    /**
     * Helper method to update metadata timestamp
     */
    private function updateMetadataTimestamp(string $id, Time $createdAt): void
    {
        $metadataPath = $this->basePendingPath . $id . DIRECTORY_SEPARATOR . 'metadata.json';
        $metadata     = json_decode(file_get_contents($metadataPath), true);

        $metadata['created_at'] = $createdAt->format('Y-m-d H:i:s');

        file_put_contents($metadataPath, json_encode($metadata));
    }
}
