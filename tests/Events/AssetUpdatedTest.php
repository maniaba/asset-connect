<?php

declare(strict_types=1);

namespace Tests\Events;

use CodeIgniter\Config\Factories;
use CodeIgniter\Test\CIUnitTestCase;
use Maniaba\AssetConnect\Asset\Asset;
use Maniaba\AssetConnect\Events\AssetEventInterface;
use Maniaba\AssetConnect\Events\AssetUpdated;
use Maniaba\AssetConnect\Exceptions\AssetException;
use Maniaba\AssetConnect\Models\AssetModel;
use Override;
use ReflectionClass;
use ReflectionMethod;

/**
 * @internal
 */
final class AssetUpdatedTest extends CIUnitTestCase
{
    private Asset $mockAsset;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockAsset = $this->createMock(Asset::class);

        // Reset factories before each test
        Factories::reset('models');
    }

    /**
     * Test that AssetUpdated implements AssetEventInterface
     */
    public function testImplementsAssetEventInterface(): void
    {
        // Arrange & Act
        $event = AssetUpdated::createFromId(1);

        // Assert
        $this->assertInstanceOf(AssetEventInterface::class, $event);
    }

    /**
     * Test creating AssetUpdated event from ID
     */
    public function testCreateFromId(): void
    {
        // Arrange & Act
        $event = AssetUpdated::createFromId(123);

        // Assert
        $this->assertInstanceOf(AssetUpdated::class, $event);
    }

    /**
     * Test getAsset method returns the correct asset when found
     */
    public function testGetAssetWhenAssetExists(): void
    {
        // Arrange
        $assetId   = 123;
        $mockAsset = $this->mockAsset;

        // Create a real AssetModel instance and inject it
        $realAssetModel = new class () extends AssetModel {
            private static ?Asset $staticMockAsset = null;

            public static function setMockAsset(?Asset $asset): void
            {
                self::$staticMockAsset = $asset;
            }

            #[Override]
            public function find($id = null)
            {
                return self::$staticMockAsset;
            }
        };

        $realAssetModel::setMockAsset($mockAsset);
        Factories::injectMock('models', AssetModel::class, $realAssetModel);

        $event = AssetUpdated::createFromId($assetId);

        // Act
        $result = $event->getAsset();

        // Assert
        $this->assertSame($this->mockAsset, $result);
    }

    /**
     * Test getAsset method throws exception when asset not found
     */
    public function testGetAssetThrowsExceptionWhenAssetNotFound(): void
    {
        // Arrange
        $assetId = 999;

        // Create a real AssetModel instance that returns null
        $realAssetModel = new class () extends AssetModel {
            #[Override]
            public function find($id = null)
            {
                return null;
            }
        };

        Factories::injectMock('models', AssetModel::class, $realAssetModel);

        $event = AssetUpdated::createFromId($assetId);

        // Expect exception
        $this->expectException(AssetException::class);
        $this->expectExceptionMessage('Asset not found for ID: 999');

        // Act
        $event->getAsset();
    }

    /**
     * Test getAsset method caches the asset after first load
     */
    public function testGetAssetCachesAssetAfterFirstLoad(): void
    {
        // Arrange
        $assetId   = 123;
        $mockAsset = $this->mockAsset;

        // Create a real AssetModel instance that counts calls
        $realAssetModel = new class () extends AssetModel {
            private static ?Asset $staticMockAsset = null;
            private static int $callCount          = 0;

            public static function setMockAsset(?Asset $asset): void
            {
                self::$staticMockAsset = $asset;
                self::$callCount       = 0; // Reset counter
            }

            #[Override]
            public function find($id = null)
            {
                self::$callCount++;

                return self::$staticMockAsset;
            }

            public static function getCallCount(): int
            {
                return self::$callCount;
            }
        };

        $realAssetModel::setMockAsset($mockAsset);
        Factories::injectMock('models', AssetModel::class, $realAssetModel);

        $event = AssetUpdated::createFromId($assetId);

        // Act - Call getAsset twice to test caching
        $result1 = $event->getAsset();
        $result2 = $event->getAsset();

        // Assert
        $this->assertSame($this->mockAsset, $result1);
        $this->assertSame($this->mockAsset, $result2);
        $this->assertSame($result1, $result2);

        // Verify find was only called once due to caching
        $this->assertSame(1, $realAssetModel::getCallCount());
    }

    /**
     * Test name method returns correct event name
     */
    public function testName(): void
    {
        // Arrange & Act
        $name = AssetUpdated::name();

        // Assert
        $this->assertSame('asset.updated', $name);
    }

    /**
     * Test that constructor is private
     */
    public function testConstructorIsPrivate(): void
    {
        // Arrange & Act
        $reflection  = new ReflectionClass(AssetUpdated::class);
        $constructor = $reflection->getConstructor();

        // Assert
        $this->assertInstanceOf(ReflectionMethod::class, $constructor);
        $this->assertTrue($constructor->isPrivate());
    }

    /**
     * Test creating events with different IDs
     */
    public function testCreateWithDifferentIds(): void
    {
        // Arrange & Act
        $event1 = AssetUpdated::createFromId(1);
        $event2 = AssetUpdated::createFromId(2);

        // Assert
        $this->assertNotSame($event1, $event2);
        $this->assertInstanceOf(AssetUpdated::class, $event1);
        $this->assertInstanceOf(AssetUpdated::class, $event2);
    }

    /**
     * Test creating event with zero ID
     */
    public function testCreateWithZeroId(): void
    {
        // Arrange & Act
        $event = AssetUpdated::createFromId(0);

        // Assert
        $this->assertInstanceOf(AssetUpdated::class, $event);
    }

    /**
     * Test creating event with negative ID
     */
    public function testCreateWithNegativeId(): void
    {
        // Arrange & Act
        $event = AssetUpdated::createFromId(-1);

        // Assert
        $this->assertInstanceOf(AssetUpdated::class, $event);
    }

    /**
     * Test getAsset with different asset IDs - simplified version
     */
    public function testGetAssetWithDifferentIdsSimplified(): void
    {
        // Arrange & Act
        $event1 = AssetUpdated::createFromId(1);
        $event2 = AssetUpdated::createFromId(2);

        // Assert - Just test that different events are created
        $this->assertNotSame($event1, $event2);
        $this->assertInstanceOf(AssetUpdated::class, $event1);
        $this->assertInstanceOf(AssetUpdated::class, $event2);

        // Test that they have different internal IDs using reflection
        $reflection = new ReflectionClass(AssetUpdated::class);
        $idProperty = $reflection->getProperty('assetId');
        $idProperty->setAccessible(true);

        $this->assertSame(1, $idProperty->getValue($event1));
        $this->assertSame(2, $idProperty->getValue($event2));
    }
}
