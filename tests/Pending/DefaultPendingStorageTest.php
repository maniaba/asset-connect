<?php

declare(strict_types=1);

namespace Tests\Pending;

use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use Maniaba\AssetConnect\Exceptions\PendingAssetException;
use Maniaba\AssetConnect\Pending\DefaultPendingStorage;
use Maniaba\AssetConnect\Pending\Interfaces\PendingSecurityTokenInterface;
use Maniaba\AssetConnect\Pending\PendingAsset;
use Override;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @internal
 */
final class DefaultPendingStorageTest extends CIUnitTestCase
{
    private DefaultPendingStorage $storage;

    /**
     * @var MockObject&PendingSecurityTokenInterface
     */
    private MockObject $mockTokenProvider;

    private string $tempFilePath;
    private string $basePendingPath;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockTokenProvider = $this->createMock(PendingSecurityTokenInterface::class);
        $this->storage           = new DefaultPendingStorage($this->mockTokenProvider);

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
     * Test constructor with custom token provider
     */
    public function testConstructorWithCustomTokenProvider(): void
    {
        // Arrange & Act
        $storage = new DefaultPendingStorage($this->mockTokenProvider);

        // Assert
        $this->assertInstanceOf(DefaultPendingStorage::class, $storage);
        $this->assertSame($this->mockTokenProvider, $storage->pendingSecurityToken());
    }

    /**
     * Test constructor with default token provider
     */
    public function testConstructorWithDefaultTokenProvider(): void
    {
        // Act
        $storage = new DefaultPendingStorage();

        // Assert
        $this->assertInstanceOf(DefaultPendingStorage::class, $storage);
        $this->assertInstanceOf(PendingSecurityTokenInterface::class, $storage->pendingSecurityToken());
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
     * Test pendingSecurityToken returns token provider
     */
    public function testPendingSecurityTokenReturnsTokenProvider(): void
    {
        // Act
        $tokenProvider = $this->storage->pendingSecurityToken();

        // Assert
        $this->assertSame($this->mockTokenProvider, $tokenProvider);
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
        $this->assertNull($result);
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
        $this->assertNull($result);
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
        $this->assertNull($result);
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
        $this->assertNull($fetchedAfterDelete);
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
