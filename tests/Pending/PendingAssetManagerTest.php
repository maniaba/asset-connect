<?php

declare(strict_types=1);

namespace Tests\Pending;

use CodeIgniter\Config\Factories;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use Maniaba\AssetConnect\Config\Asset as AssetConfig;
use Maniaba\AssetConnect\Exceptions\PendingAssetException;
use Maniaba\AssetConnect\Pending\DefaultPendingStorage;
use Maniaba\AssetConnect\Pending\Interfaces\PendingStorageInterface;
use Maniaba\AssetConnect\Pending\PendingAsset;
use Maniaba\AssetConnect\Pending\PendingAssetManager;
use Override;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;

/**
 * @internal
 */
final class PendingAssetManagerTest extends CIUnitTestCase
{
    /**
     * @var MockObject&PendingStorageInterface
     */
    private MockObject $mockStorage;

    private AssetConfig $mockAssetConfig;
    private string $tempFilePath;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockStorage     = $this->createMock(PendingStorageInterface::class);
        $this->mockAssetConfig = $this->createMock(AssetConfig::class);

        // Create a temporary file for testing
        $this->tempFilePath = tempnam(sys_get_temp_dir(), 'test_manager_');
        file_put_contents($this->tempFilePath, 'test content');

        // Setup global function mocks
        $this->setupGlobalFunctionMocks();
    }

    #[Override]
    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up temporary file
        if (file_exists($this->tempFilePath)) {
            unlink($this->tempFilePath);
        }

        Factories::reset('config');
    }

    /**
     * Setup global function mocks
     */
    private function setupGlobalFunctionMocks(): void
    {
        // Inject mock for config
        $this->mockAssetConfig->pendingStorage = $this->mockStorage::class;
        Factories::injectMock('config', 'Asset', $this->mockAssetConfig);
    }

    /**
     * Test making PendingAssetManager with custom storage
     */
    public function testMakeWithCustomStorage(): void
    {
        // Act
        $manager = PendingAssetManager::make($this->mockStorage);

        // Assert
        $this->assertInstanceOf(PendingAssetManager::class, $manager);
    }

    /**
     * Test making PendingAssetManager with default storage from config
     */
    public function testMakeWithDefaultStorageFromConfig(): void
    {
        // Arrange
        $this->mockAssetConfig->pendingStorage = DefaultPendingStorage::class;

        // Act
        $manager = PendingAssetManager::make();

        // Assert
        $this->assertInstanceOf(PendingAssetManager::class, $manager);
    }

    /**
     * Test fetchById returns pending asset when found and not expired
     */
    public function testFetchByIdReturnsAssetWhenFoundAndNotExpired(): void
    {
        // Arrange
        $id         = 'test-id-123';
        $ttlSeconds = 3600;
        $createdAt  = Time::now()->subSeconds(1800); // Created 30 minutes ago

        // Act
        $result = $this->assertNonExpiredAssetIsReturned($id, $ttlSeconds, $createdAt);

        // Assert
        $this->assertInstanceOf(PendingAsset::class, $result);
        $this->assertSame($id, $result->id);
    }

    /**
     * Test fetchById returns null when asset not found
     */
    public function testFetchByIdReturnsNullWhenNotFound(): void
    {
        // Arrange
        $id = 'non-existent-id';

        $this->mockStorage->method('fetchById')->with($id)->willReturn(null);
        $this->mockStorage->method('getDefaultTTLSeconds')->willReturn(3600);

        $manager = PendingAssetManager::make($this->mockStorage);

        // Act
        $result = $manager->fetchById($id);

        // Assert
        $this->assertNotInstanceOf(PendingAsset::class, $result);
    }

    /**
     * Test fetchById returns null when asset is expired
     */
    public function testFetchByIdReturnsNullWhenExpired(): void
    {
        // Arrange
        $id         = 'expired-id';
        $ttlSeconds = 3600;
        $createdAt  = Time::now()->subSeconds(7200); // Created 2 hours ago

        // Act
        $result = $this->assertExpiredAssetIsDeleted($id, $ttlSeconds, $createdAt);

        // Assert
        $this->assertNotInstanceOf(PendingAsset::class, $result);
    }

    /**
     * Test fetchById handles deletion failure of expired asset gracefully
     */
    public function testFetchByIdHandlesDeletionFailureGracefully(): void
    {
        // Arrange
        $id         = 'expired-id';
        $ttlSeconds = 3600;
        $createdAt  = Time::now()->subSeconds(7200); // Created 2 hours ago

        $pendingAsset = PendingAsset::createFromFile($this->tempFilePath);
        $pendingAsset->setId($id);
        $this->setPrivateProperty($pendingAsset, 'created_at', $createdAt);

        $this->mockStorage->method('fetchById')->with($id)->willReturn($pendingAsset);
        $this->mockStorage->method('getDefaultTTLSeconds')->willReturn($ttlSeconds);
        $this->mockStorage->method('deleteById')->with($id)->willThrowException(
            new PendingAssetException('Delete failed'),
        );

        $manager = PendingAssetManager::make($this->mockStorage);

        // Act
        $result = $manager->fetchById($id);

        // Assert - should return null even if deletion fails
        $this->assertNotInstanceOf(PendingAsset::class, $result);
    }

    /**
     * Test fetchById with asset exactly at expiration boundary
     */
    public function testFetchByIdWithAssetAtExpirationBoundary(): void
    {
        // Arrange
        $id         = 'boundary-id';
        $ttlSeconds = 3600;
        $createdAt  = Time::now()->subSeconds(3600); // Created exactly TTL seconds ago

        // Act
        $result = $this->assertNonExpiredAssetIsReturned($id, $ttlSeconds, $createdAt);

        // Assert - should NOT be expired (< is used in comparison, not <=)
        // expiresAt = createdAt + TTL = now - 3600 + 3600 = now
        // now < now is false, so asset is still valid
        $this->assertInstanceOf(PendingAsset::class, $result);
        $this->assertSame($id, $result->id);
    }

    /**
     * Test fetchById with asset one second past expiration boundary
     */
    public function testFetchByIdWithAssetOneSecondPastExpiration(): void
    {
        // Arrange
        $id         = 'expired-boundary-id';
        $ttlSeconds = 3600;
        $createdAt  = Time::now()->subSeconds(3601); // Created 1 second past TTL

        // Act
        $result = $this->assertExpiredAssetIsDeleted($id, $ttlSeconds, $createdAt);

        // Assert - should be expired
        $this->assertNotInstanceOf(PendingAsset::class, $result);
    }

    /**
     * Test deleteById returns true on successful deletion
     */
    public function testDeleteByIdReturnsTrue(): void
    {
        // Arrange
        $id = 'delete-id';

        $this->mockStorage->method('deleteById')->with($id)->willReturn(true);

        $manager = PendingAssetManager::make($this->mockStorage);

        // Act
        $result = $manager->deleteById($id);

        // Assert
        $this->assertTrue($result);
    }

    /**
     * Test deleteById returns false on failed deletion
     */
    public function testDeleteByIdReturnsFalse(): void
    {
        // Arrange
        $id = 'delete-id';

        $this->mockStorage->method('deleteById')->with($id)->willReturn(false);

        $manager = PendingAssetManager::make($this->mockStorage);

        // Act
        $result = $manager->deleteById($id);

        // Assert
        $this->assertFalse($result);
    }

    /**
     * Test store method with default TTL
     */
    public function testStoreWithDefaultTTL(): void
    {
        // Arrange
        $generatedId  = 'generated-id-123';
        $defaultTTL   = 86400;
        $pendingAsset = PendingAsset::createFromFile($this->tempFilePath);

        $this->mockStorage->method('generatePendingId')->willReturn($generatedId);
        $this->mockStorage->method('getDefaultTTLSeconds')->willReturn($defaultTTL);
        $this->mockStorage->expects($this->once())
            ->method('store')
            ->with($this->identicalTo($pendingAsset), $generatedId);

        $manager = PendingAssetManager::make($this->mockStorage);

        // Act
        $manager->store($pendingAsset);

        // Assert
        $this->assertSame($generatedId, $pendingAsset->id);
        $this->assertSame($defaultTTL, $pendingAsset->ttl);
    }

    /**
     * Test store method with custom TTL
     */
    public function testStoreWithCustomTTL(): void
    {
        // Arrange
        $generatedId  = 'generated-id-456';
        $customTTL    = 7200;
        $pendingAsset = PendingAsset::createFromFile($this->tempFilePath);

        $this->mockStorage->method('generatePendingId')->willReturn($generatedId);
        $this->mockStorage->expects($this->once())
            ->method('store')
            ->with($this->identicalTo($pendingAsset), $generatedId);

        $manager = PendingAssetManager::make($this->mockStorage);

        // Act
        $manager->store($pendingAsset, $customTTL);

        // Assert
        $this->assertSame($generatedId, $pendingAsset->id);
        $this->assertSame($customTTL, $pendingAsset->ttl);
    }

    /**
     * Test store method updates pending asset properties
     */
    public function testStoreUpdatesPendingAssetProperties(): void
    {
        // Arrange
        $generatedId  = 'new-generated-id';
        $ttl          = 3600;
        $pendingAsset = PendingAsset::createFromFile($this->tempFilePath);

        // Verify initial state
        $this->assertSame('', $pendingAsset->id);
        $this->assertSame(0, $pendingAsset->ttl);

        $this->mockStorage->method('generatePendingId')->willReturn($generatedId);
        $this->mockStorage->method('getDefaultTTLSeconds')->willReturn($ttl);
        $this->mockStorage->method('store');

        $manager = PendingAssetManager::make($this->mockStorage);

        // Act
        $manager->store($pendingAsset);

        // Assert
        $this->assertSame($generatedId, $pendingAsset->id);
        $this->assertSame($ttl, $pendingAsset->ttl);
    }

    /**
     * Test cleanExpiredPendingAssets delegates to storage
     */
    public function testCleanExpiredPendingAssetsDelegatesToStorage(): void
    {
        // Arrange
        $this->mockStorage->expects($this->once())
            ->method('cleanExpiredPendingAssets');

        $manager = PendingAssetManager::make($this->mockStorage);

        // Act
        $manager->cleanExpiredPendingAssets();

        // Assert - expectation verified by PHPUnit
        $this->assertTrue(true);
    }

    /**
     * Test store propagates exception from storage
     */
    public function testStorePropagatesExceptionFromStorage(): void
    {
        // Arrange
        $pendingAsset = PendingAsset::createFromFile($this->tempFilePath);
        $exception    = new PendingAssetException('Storage error');

        $this->mockStorage->method('generatePendingId')->willReturn('test-id');
        $this->mockStorage->method('getDefaultTTLSeconds')->willReturn(3600);
        $this->mockStorage->method('store')->willThrowException($exception);

        $manager = PendingAssetManager::make($this->mockStorage);

        // Act & Assert
        $this->expectException(PendingAssetException::class);
        $this->expectExceptionMessage('Storage error');
        $manager->store($pendingAsset);
    }

    /**
     * Test deleteById propagates exception from storage
     */
    public function testDeleteByIdPropagatesExceptionFromStorage(): void
    {
        // Arrange
        $id        = 'error-id';
        $exception = new PendingAssetException('Delete error');

        $this->mockStorage->method('deleteById')->with($id)->willThrowException($exception);

        $manager = PendingAssetManager::make($this->mockStorage);

        // Act & Assert
        $this->expectException(PendingAssetException::class);
        $this->expectExceptionMessage('Delete error');
        $manager->deleteById($id);
    }

    /**
     * Test fetchById with zero TTL
     */
    public function testFetchByIdWithZeroTTL(): void
    {
        // Arrange
        $id         = 'zero-ttl-id';
        $ttlSeconds = 0;
        $createdAt  = Time::now()->subSeconds(1);

        // Act
        $result = $this->assertExpiredAssetIsDeleted($id, $ttlSeconds, $createdAt);

        // Assert - should be expired immediately
        $this->assertNotInstanceOf(PendingAsset::class, $result);
    }

    /**
     * Test fetchById with very large TTL
     */
    public function testFetchByIdWithVeryLargeTTL(): void
    {
        // Arrange
        $id         = 'large-ttl-id';
        $ttlSeconds = 31536000; // 1 year
        $createdAt  = Time::now()->subSeconds(100);

        $pendingAsset = PendingAsset::createFromFile($this->tempFilePath);
        $pendingAsset->setId($id);
        $this->setPrivateProperty($pendingAsset, 'created_at', $createdAt);

        $this->mockStorage->method('fetchById')->with($id)->willReturn($pendingAsset);
        $this->mockStorage->method('getDefaultTTLSeconds')->willReturn($ttlSeconds);

        $manager = PendingAssetManager::make($this->mockStorage);

        // Act
        $result = $manager->fetchById($id);

        // Assert
        $this->assertInstanceOf(PendingAsset::class, $result);
    }

    /**
     * Test store with zero TTL
     */
    public function testStoreWithZeroTTL(): void
    {
        // Arrange
        $generatedId  = 'zero-ttl-store';
        $zeroTTL      = 0;
        $pendingAsset = PendingAsset::createFromFile($this->tempFilePath);

        $this->mockStorage->method('generatePendingId')->willReturn($generatedId);
        $this->mockStorage->method('store');

        $manager = PendingAssetManager::make($this->mockStorage);

        // Act
        $manager->store($pendingAsset, $zeroTTL);

        // Assert
        $this->assertSame($zeroTTL, $pendingAsset->ttl);
    }

    /**
     * Test store with existing ID updates metadata only (not file)
     * When ID is provided, it's an update operation - only metadata should be updated
     */
    public function testStoreWithExistingIdUpdatesMetadataOnly(): void
    {
        // Arrange
        $existingId   = 'existing-asset-id';
        $customTTL    = 7200;
        $pendingAsset = PendingAsset::createFromFile($this->tempFilePath);

        // Simulate an asset that already has an ID (update scenario)
        $pendingAsset->setId($existingId);

        // Storage should NOT generate a new ID when one is already set
        $this->mockStorage->expects($this->never())->method('generatePendingId');

        // Storage should receive the store call with the existing ID
        $this->mockStorage->expects($this->once())
            ->method('store')
            ->with(
                $this->identicalTo($pendingAsset),
                $existingId, // The existing ID should be passed
            );

        $manager = PendingAssetManager::make($this->mockStorage);

        // Act - store with existing ID (update scenario)
        $manager->store($pendingAsset, $customTTL);

        // Assert - ID should remain the same (update, not create)
        $this->assertSame($existingId, $pendingAsset->id);
        $this->assertSame($customTTL, $pendingAsset->ttl);
    }

    /**
     * Test store without ID generates new ID (create scenario)
     */
    public function testStoreWithoutIdGeneratesNewId(): void
    {
        // Arrange
        $newGeneratedId = 'newly-generated-id';
        $defaultTTL     = 3600;
        $pendingAsset   = PendingAsset::createFromFile($this->tempFilePath);

        // Asset has no ID initially (create scenario)
        $this->assertSame('', $pendingAsset->id);

        // Storage should generate a new ID
        $this->mockStorage->expects($this->once())
            ->method('generatePendingId')
            ->willReturn($newGeneratedId);

        $this->mockStorage->method('getDefaultTTLSeconds')->willReturn($defaultTTL);

        // Storage should receive the store call with the newly generated ID
        $this->mockStorage->expects($this->once())
            ->method('store')
            ->with(
                $this->identicalTo($pendingAsset),
                $newGeneratedId,
            );

        $manager = PendingAssetManager::make($this->mockStorage);

        // Act - store without ID (create scenario)
        $manager->store($pendingAsset);

        // Assert - new ID should be assigned
        $this->assertSame($newGeneratedId, $pendingAsset->id);
        $this->assertSame($defaultTTL, $pendingAsset->ttl);
    }

    /**
     * Test multiple sequential operations
     */
    public function testMultipleSequentialOperations(): void
    {
        // Arrange
        $id1 = 'asset-1';
        $id2 = 'asset-2';

        $asset1 = PendingAsset::createFromFile($this->tempFilePath);
        $asset2 = PendingAsset::createFromFile($this->tempFilePath);

        $this->mockStorage->method('generatePendingId')->willReturnOnConsecutiveCalls($id1, $id2);
        $this->mockStorage->method('getDefaultTTLSeconds')->willReturn(3600);
        $this->mockStorage->method('store');
        $this->mockStorage->method('deleteById')->willReturn(true);

        $manager = PendingAssetManager::make($this->mockStorage);

        // Act
        $manager->store($asset1);
        $manager->store($asset2);
        $result1 = $manager->deleteById($id1);
        $result2 = $manager->deleteById($id2);

        // Assert
        $this->assertSame($id1, $asset1->id);
        $this->assertSame($id2, $asset2->id);
        $this->assertTrue($result1);
        $this->assertTrue($result2);
    }

    /**
     * Test fetchById handles various exception types during deletion
     */
    public function testFetchByIdHandlesVariousExceptionTypesDuringDeletion(): void
    {
        // Arrange
        $id         = 'exception-id';
        $ttlSeconds = 3600;
        $createdAt  = Time::now()->subSeconds(7200);

        $pendingAsset = PendingAsset::createFromFile($this->tempFilePath);
        $pendingAsset->setId($id);
        $this->setPrivateProperty($pendingAsset, 'created_at', $createdAt);

        $this->mockStorage->method('fetchById')->with($id)->willReturn($pendingAsset);
        $this->mockStorage->method('getDefaultTTLSeconds')->willReturn($ttlSeconds);
        $this->mockStorage->method('deleteById')->willThrowException(
            new RuntimeException('Unexpected error'),
        );

        $manager = PendingAssetManager::make($this->mockStorage);

        // Act
        $result = $manager->fetchById($id);

        // Assert - should handle exception gracefully and return null
        $this->assertNotInstanceOf(PendingAsset::class, $result);
    }

    /**
     * Helper method to create and setup a non-expired pending asset
     *
     * @param string $id         The asset ID
     * @param int    $ttlSeconds The TTL in seconds
     * @param Time   $createdAt  The creation time
     *
     * @return mixed The result of fetchById
     */
    private function assertNonExpiredAssetIsReturned(string $id, int $ttlSeconds, Time $createdAt)
    {
        $pendingAsset = PendingAsset::createFromFile($this->tempFilePath);
        $pendingAsset->setId($id);
        $this->setPrivateProperty($pendingAsset, 'created_at', $createdAt);

        $this->mockStorage->method('fetchById')->with($id)->willReturn($pendingAsset);
        $this->mockStorage->method('getDefaultTTLSeconds')->willReturn($ttlSeconds);
        $this->mockStorage->expects($this->never())->method('deleteById');

        $manager = PendingAssetManager::make($this->mockStorage);

        return $manager->fetchById($id);
    }

    /**
     * Helper method to test expired asset scenario with automatic deletion
     *
     * @param string $id         The asset ID
     * @param int    $ttlSeconds The TTL in seconds
     * @param Time   $createdAt  The creation time
     *
     * @return mixed The result of fetchById
     */
    private function assertExpiredAssetIsDeleted(string $id, int $ttlSeconds, Time $createdAt)
    {
        $pendingAsset = PendingAsset::createFromFile($this->tempFilePath);
        $pendingAsset->setId($id);
        $this->setPrivateProperty($pendingAsset, 'created_at', $createdAt);

        $this->mockStorage->method('fetchById')->with($id)->willReturn($pendingAsset);
        $this->mockStorage->method('getDefaultTTLSeconds')->willReturn($ttlSeconds);
        $this->mockStorage->expects($this->once())->method('deleteById')->with($id)->willReturn(true);

        $manager = PendingAssetManager::make($this->mockStorage);

        return $manager->fetchById($id);
    }
}
