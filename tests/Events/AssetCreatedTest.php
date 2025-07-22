<?php

declare(strict_types=1);

namespace Tests\Events;

use CodeIgniter\Entity\Entity;
use CodeIgniter\Test\CIUnitTestCase;
use Maniaba\AssetConnect\Asset\Asset;
use Maniaba\AssetConnect\Events\AssetCreated;
use Maniaba\AssetConnect\Events\AssetEventInterface;
use Override;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;
use ReflectionMethod;

/**
 * @internal
 */
final class AssetCreatedTest extends CIUnitTestCase
{
    private MockObject $mockAsset;
    private MockObject $mockEntity;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockAsset  = $this->createMock(Asset::class);
        $this->mockEntity = $this->createMock(Entity::class);
    }

    /**
     * Test that AssetCreated implements AssetEventInterface
     */
    public function testImplementsAssetEventInterface(): void
    {
        // Arrange & Act
        $event = AssetCreated::createFromAsset($this->mockAsset, $this->mockEntity);

        // Assert
        $this->assertInstanceOf(AssetEventInterface::class, $event);
    }

    /**
     * Test creating AssetCreated event from asset
     */
    public function testCreateFromAsset(): void
    {
        // Arrange & Act
        $event = AssetCreated::createFromAsset($this->mockAsset, $this->mockEntity);

        // Assert
        $this->assertInstanceOf(AssetCreated::class, $event);
        $this->assertSame($this->mockAsset, $event->getAsset());
        $this->assertSame($this->mockEntity, $event->getSubjectEntity());
    }

    /**
     * Test getAsset method returns the correct asset
     */
    public function testGetAsset(): void
    {
        // Arrange
        $event = AssetCreated::createFromAsset($this->mockAsset, $this->mockEntity);

        // Act
        $result = $event->getAsset();

        // Assert
        $this->assertSame($this->mockAsset, $result);
    }

    /**
     * Test getSubjectEntity method returns the correct entity
     */
    public function testGetSubjectEntity(): void
    {
        // Arrange
        $event = AssetCreated::createFromAsset($this->mockAsset, $this->mockEntity);

        // Act
        $result = $event->getSubjectEntity();

        // Assert
        $this->assertSame($this->mockEntity, $result);
    }

    /**
     * Test name method returns correct event name
     */
    public function testName(): void
    {
        // Arrange & Act
        $name = AssetCreated::name();

        // Assert
        $this->assertSame('asset.created', $name);
    }

    /**
     * Test that the event is readonly
     */
    public function testEventIsReadonly(): void
    {
        // Arrange
        $event = AssetCreated::createFromAsset($this->mockAsset, $this->mockEntity);

        // Act & Assert - readonly classes don't allow property modification
        // This test ensures the class maintains its readonly state
        $reflection = new ReflectionClass($event);
        $this->assertTrue($reflection->isReadOnly());
    }

    /**
     * Test that constructor is private
     */
    public function testConstructorIsPrivate(): void
    {
        // Arrange & Act
        $reflection  = new ReflectionClass(AssetCreated::class);
        $constructor = $reflection->getConstructor();

        // Assert
        $this->assertInstanceOf(ReflectionMethod::class, $constructor);
        $this->assertTrue($constructor->isPrivate());
    }

    /**
     * Test with different asset and entity instances
     */
    public function testWithDifferentInstances(): void
    {
        // Arrange
        $asset1  = $this->createMock(Asset::class);
        $entity1 = $this->createMock(Entity::class);
        $asset2  = $this->createMock(Asset::class);
        $entity2 = $this->createMock(Entity::class);

        // Act
        $event1 = AssetCreated::createFromAsset($asset1, $entity1);
        $event2 = AssetCreated::createFromAsset($asset2, $entity2);

        // Assert
        $this->assertSame($asset1, $event1->getAsset());
        $this->assertSame($entity1, $event1->getSubjectEntity());
        $this->assertSame($asset2, $event2->getAsset());
        $this->assertSame($entity2, $event2->getSubjectEntity());
        $this->assertNotSame($event1->getAsset(), $event2->getAsset());
        $this->assertNotSame($event1->getSubjectEntity(), $event2->getSubjectEntity());
    }
}
