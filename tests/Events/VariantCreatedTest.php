<?php

declare(strict_types=1);

namespace Tests\Events;

use CodeIgniter\Test\CIUnitTestCase;
use Maniaba\AssetConnect\Asset\Asset;
use Maniaba\AssetConnect\AssetVariants\AssetVariant;
use Maniaba\AssetConnect\Events\AssetEventInterface;
use Maniaba\AssetConnect\Events\VariantCreated;
use ReflectionClass;
use ReflectionMethod;

/**
 * @internal
 */
final class VariantCreatedTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test that VariantCreated implements AssetEventInterface
     */
    public function testImplementsAssetEventInterface(): void
    {
        // Arrange & Act
        $event = new VariantCreated($this->createStub(AssetVariant::class), $this->createStub(Asset::class));

        // Assert
        $this->assertInstanceOf(AssetEventInterface::class, $event);
    }

    /**
     * Test creating VariantCreated event
     */
    public function testCreateVariantCreated(): void
    {
        // Arrange & Act
        $event = new VariantCreated($this->createStub(AssetVariant::class), $this->createStub(Asset::class));

        // Assert
        $this->assertInstanceOf(VariantCreated::class, $event);
        $this->assertSame($this->createStub(Asset::class), $event->getAsset());
        $this->assertSame($this->createStub(AssetVariant::class), $event->getVariant());
    }

    /**
     * Test getAsset method returns the correct asset
     */
    public function testGetAsset(): void
    {
        // Arrange
        $event = new VariantCreated($this->createStub(AssetVariant::class), $this->createStub(Asset::class));

        // Act
        $result = $event->getAsset();

        // Assert
        $this->assertSame($this->createStub(Asset::class), $result);
    }

    /**
     * Test getVariant method returns the correct variant
     */
    public function testGetVariant(): void
    {
        // Arrange
        $event = new VariantCreated($this->createStub(AssetVariant::class), $this->createStub(Asset::class));

        // Act
        $result = $event->getVariant();

        // Assert
        $this->assertSame($this->createStub(AssetVariant::class), $result);
    }

    /**
     * Test name method returns correct event name
     */
    public function testName(): void
    {
        // Arrange & Act
        $name = VariantCreated::name();

        // Assert
        $this->assertSame('variant.created', $name);
    }

    /**
     * Test that the event is readonly
     */
    public function testEventIsReadonly(): void
    {
        // Arrange
        $event = new VariantCreated($this->createStub(AssetVariant::class), $this->createStub(Asset::class));

        // Act & Assert - readonly classes don't allow property modification
        $reflection = new ReflectionClass($event);
        $this->assertTrue($reflection->isReadOnly());
    }

    /**
     * Test that constructor is public (unlike other events)
     */
    public function testConstructorIsPublic(): void
    {
        // Arrange & Act
        $reflection  = new ReflectionClass(VariantCreated::class);
        $constructor = $reflection->getConstructor();

        // Assert
        $this->assertInstanceOf(ReflectionMethod::class, $constructor);
        $this->assertTrue($constructor->isPublic());
    }

    /**
     * Test with different variant and asset instances
     */
    public function testWithDifferentInstances(): void
    {
        // Arrange
        $variant1 = $this->createStub(AssetVariant::class);
        $asset1   = $this->createStub(Asset::class);
        $variant2 = $this->createStub(AssetVariant::class);
        $asset2   = $this->createStub(Asset::class);

        // Act
        $event1 = new VariantCreated($variant1, $asset1);
        $event2 = new VariantCreated($variant2, $asset2);

        // Assert
        $this->assertSame($variant1, $event1->getVariant());
        $this->assertSame($asset1, $event1->getAsset());
        $this->assertSame($variant2, $event2->getVariant());
        $this->assertSame($asset2, $event2->getAsset());
        $this->assertNotSame($event1->getVariant(), $event2->getVariant());
        $this->assertNotSame($event1->getAsset(), $event2->getAsset());
    }

    /**
     * Test multiple instances are independent
     */
    public function testMultipleInstancesAreIndependent(): void
    {
        // Arrange
        $variant1 = $this->createStub(AssetVariant::class);
        $asset1   = $this->createStub(Asset::class);
        $variant2 = $this->createStub(AssetVariant::class);
        $asset2   = $this->createStub(Asset::class);

        // Act
        $event1 = new VariantCreated($variant1, $asset1);
        $event2 = new VariantCreated($variant2, $asset2);

        // Assert
        $this->assertNotSame($event1, $event2);
        $this->assertInstanceOf(VariantCreated::class, $event1);
        $this->assertInstanceOf(VariantCreated::class, $event2);
    }

    /**
     * Test that same variant can be used with different assets
     */
    public function testSameVariantWithDifferentAssets(): void
    {
        // Arrange
        $asset1 = $this->createStub(Asset::class);
        $asset2 = $this->createStub(Asset::class);

        // Act
        $event1 = new VariantCreated($this->createStub(AssetVariant::class), $asset1);
        $event2 = new VariantCreated($this->createStub(AssetVariant::class), $asset2);

        // Assert
        $this->assertSame($this->createStub(AssetVariant::class), $event1->getVariant());
        $this->assertSame($this->createStub(AssetVariant::class), $event2->getVariant());
        $this->assertSame($asset1, $event1->getAsset());
        $this->assertSame($asset2, $event2->getAsset());
        $this->assertNotSame($event1->getAsset(), $event2->getAsset());
    }

    /**
     * Test that same asset can be used with different variants
     */
    public function testSameAssetWithDifferentVariants(): void
    {
        // Arrange
        $variant1 = $this->createStub(AssetVariant::class);
        $variant2 = $this->createStub(AssetVariant::class);

        // Act
        $event1 = new VariantCreated($variant1, $this->createStub(Asset::class));
        $event2 = new VariantCreated($variant2, $this->createStub(Asset::class));

        // Assert
        $this->assertSame($variant1, $event1->getVariant());
        $this->assertSame($variant2, $event2->getVariant());
        $this->assertSame($this->createStub(Asset::class), $event1->getAsset());
        $this->assertSame($this->createStub(Asset::class), $event2->getAsset());
        $this->assertNotSame($event1->getVariant(), $event2->getVariant());
    }
}
