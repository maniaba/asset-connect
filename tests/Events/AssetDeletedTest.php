<?php

declare(strict_types=1);

namespace Tests\Events;

use CodeIgniter\Test\CIUnitTestCase;
use Maniaba\AssetConnect\Asset\Asset;
use Maniaba\AssetConnect\Events\AssetDeleted;
use Maniaba\AssetConnect\Events\AssetEventInterface;
use Override;
use ReflectionClass;
use ReflectionMethod;

/**
 * @internal
 */
final class AssetDeletedTest extends CIUnitTestCase
{
    private Asset $mockAsset;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockAsset = $this->createMock(Asset::class);
    }

    /**
     * Test that AssetDeleted implements AssetEventInterface
     */
    public function testImplementsAssetEventInterface(): void
    {
        // Arrange & Act
        $event = AssetDeleted::createFromAsset($this->mockAsset);

        // Assert
        $this->assertInstanceOf(AssetEventInterface::class, $event);
    }

    /**
     * Test creating AssetDeleted event from asset
     */
    public function testCreateFromAsset(): void
    {
        // Arrange & Act
        $event = AssetDeleted::createFromAsset($this->mockAsset);

        // Assert
        $this->assertInstanceOf(AssetDeleted::class, $event);
        $this->assertSame($this->mockAsset, $event->getAsset());
    }

    /**
     * Test getAsset method returns the correct asset
     */
    public function testGetAsset(): void
    {
        // Arrange
        $event = AssetDeleted::createFromAsset($this->mockAsset);

        // Act
        $result = $event->getAsset();

        // Assert
        $this->assertSame($this->mockAsset, $result);
    }

    /**
     * Test name method returns correct event name
     */
    public function testName(): void
    {
        // Arrange & Act
        $name = AssetDeleted::name();

        // Assert
        $this->assertSame('asset.deleted', $name);
    }

    /**
     * Test that the event is readonly
     */
    public function testEventIsReadonly(): void
    {
        // Arrange
        $event = AssetDeleted::createFromAsset($this->mockAsset);

        // Act & Assert - readonly classes don't allow property modification
        $reflection = new ReflectionClass($event);
        $this->assertTrue($reflection->isReadOnly());
    }

    /**
     * Test that constructor is private
     */
    public function testConstructorIsPrivate(): void
    {
        // Arrange & Act
        $reflection  = new ReflectionClass(AssetDeleted::class);
        $constructor = $reflection->getConstructor();

        // Assert
        $this->assertInstanceOf(ReflectionMethod::class, $constructor);
        $this->assertTrue($constructor->isPrivate());
    }

    /**
     * Test with different asset instances
     */
    public function testWithDifferentAssetInstances(): void
    {
        // Arrange
        $asset1 = $this->createMock(Asset::class);
        $asset2 = $this->createMock(Asset::class);

        // Act
        $event1 = AssetDeleted::createFromAsset($asset1);
        $event2 = AssetDeleted::createFromAsset($asset2);

        // Assert
        $this->assertSame($asset1, $event1->getAsset());
        $this->assertSame($asset2, $event2->getAsset());
        $this->assertNotSame($event1->getAsset(), $event2->getAsset());
    }

    /**
     * Test multiple instances are independent
     */
    public function testMultipleInstancesAreIndependent(): void
    {
        // Arrange
        $asset1 = $this->createMock(Asset::class);
        $asset2 = $this->createMock(Asset::class);

        // Act
        $event1 = AssetDeleted::createFromAsset($asset1);
        $event2 = AssetDeleted::createFromAsset($asset2);

        // Assert
        $this->assertNotSame($event1, $event2);
        $this->assertInstanceOf(AssetDeleted::class, $event1);
        $this->assertInstanceOf(AssetDeleted::class, $event2);
    }
}
