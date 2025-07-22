<?php

declare(strict_types=1);

namespace Tests\Events;

use CodeIgniter\Test\CIUnitTestCase;
use Maniaba\AssetConnect\Asset\Asset;
use Maniaba\AssetConnect\AssetVariants\AssetVariant;
use Maniaba\AssetConnect\Events\AssetEventInterface;
use Maniaba\AssetConnect\Events\VariantCreated;
use Override;
use ReflectionClass;
use ReflectionMethod;

/**
 * @internal
 */
final class VariantCreatedTest extends CIUnitTestCase
{
    private Asset $mockAsset;
    private AssetVariant $mockVariant;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockAsset   = $this->createMock(Asset::class);
        $this->mockVariant = $this->createMock(AssetVariant::class);
    }

    /**
     * Test that VariantCreated implements AssetEventInterface
     */
    public function testImplementsAssetEventInterface(): void
    {
        // Arrange & Act
        $event = new VariantCreated($this->mockVariant, $this->mockAsset);

        // Assert
        $this->assertInstanceOf(AssetEventInterface::class, $event);
    }

    /**
     * Test creating VariantCreated event
     */
    public function testCreateVariantCreated(): void
    {
        // Arrange & Act
        $event = new VariantCreated($this->mockVariant, $this->mockAsset);

        // Assert
        $this->assertInstanceOf(VariantCreated::class, $event);
        $this->assertSame($this->mockAsset, $event->getAsset());
        $this->assertSame($this->mockVariant, $event->getVariant());
    }

    /**
     * Test getAsset method returns the correct asset
     */
    public function testGetAsset(): void
    {
        // Arrange
        $event = new VariantCreated($this->mockVariant, $this->mockAsset);

        // Act
        $result = $event->getAsset();

        // Assert
        $this->assertSame($this->mockAsset, $result);
    }

    /**
     * Test getVariant method returns the correct variant
     */
    public function testGetVariant(): void
    {
        // Arrange
        $event = new VariantCreated($this->mockVariant, $this->mockAsset);

        // Act
        $result = $event->getVariant();

        // Assert
        $this->assertSame($this->mockVariant, $result);
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
        $event = new VariantCreated($this->mockVariant, $this->mockAsset);

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
        $variant1 = $this->createMock(AssetVariant::class);
        $asset1   = $this->createMock(Asset::class);
        $variant2 = $this->createMock(AssetVariant::class);
        $asset2   = $this->createMock(Asset::class);

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
        $variant1 = $this->createMock(AssetVariant::class);
        $asset1   = $this->createMock(Asset::class);
        $variant2 = $this->createMock(AssetVariant::class);
        $asset2   = $this->createMock(Asset::class);

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
        $asset1 = $this->createMock(Asset::class);
        $asset2 = $this->createMock(Asset::class);

        // Act
        $event1 = new VariantCreated($this->mockVariant, $asset1);
        $event2 = new VariantCreated($this->mockVariant, $asset2);

        // Assert
        $this->assertSame($this->mockVariant, $event1->getVariant());
        $this->assertSame($this->mockVariant, $event2->getVariant());
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
        $variant1 = $this->createMock(AssetVariant::class);
        $variant2 = $this->createMock(AssetVariant::class);

        // Act
        $event1 = new VariantCreated($variant1, $this->mockAsset);
        $event2 = new VariantCreated($variant2, $this->mockAsset);

        // Assert
        $this->assertSame($variant1, $event1->getVariant());
        $this->assertSame($variant2, $event2->getVariant());
        $this->assertSame($this->mockAsset, $event1->getAsset());
        $this->assertSame($this->mockAsset, $event2->getAsset());
        $this->assertNotSame($event1->getVariant(), $event2->getVariant());
    }
}
