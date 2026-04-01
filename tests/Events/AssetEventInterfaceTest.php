<?php

declare(strict_types=1);

namespace Tests\Events;

use CodeIgniter\Test\CIUnitTestCase;
use Maniaba\AssetConnect\Asset\Asset;
use Maniaba\AssetConnect\Events\AssetEventInterface;
use Override;
use ReflectionClass;
use ReflectionType;

/**
 * @internal
 */
final class AssetEventInterfaceTest extends CIUnitTestCase
{
    private Asset $mockAsset;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockAsset = $this->createMock(Asset::class);
    }

    /**
     * Test that interface defines required methods
     */
    public function testInterfaceDefinesRequiredMethods(): void
    {
        // Arrange & Act
        $reflection = new ReflectionClass(AssetEventInterface::class);
        $methods    = $reflection->getMethods();

        // Assert
        $this->assertTrue($reflection->isInterface());

        $methodNames = array_map(static fn ($method) => $method->getName(), $methods);
        $this->assertContains('getAsset', $methodNames);
        $this->assertContains('name', $methodNames);
        $this->assertCount(2, $methods);
    }

    /**
     * Test getAsset method signature
     */
    public function testGetAssetMethodSignature(): void
    {
        // Arrange & Act
        $reflection = new ReflectionClass(AssetEventInterface::class);
        $method     = $reflection->getMethod('getAsset');

        // Assert
        $this->assertTrue($method->isPublic());
        $this->assertFalse($method->isStatic());
        $this->assertSame('getAsset', $method->getName());

        $returnType = $method->getReturnType();
        $this->assertInstanceOf(ReflectionType::class, $returnType);
        $this->assertSame('Maniaba\AssetConnect\Asset\Asset', $returnType->getName());
    }

    /**
     * Test name method signature
     */
    public function testNameMethodSignature(): void
    {
        // Arrange & Act
        $reflection = new ReflectionClass(AssetEventInterface::class);
        $method     = $reflection->getMethod('name');

        // Assert
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());
        $this->assertSame('name', $method->getName());

        $returnType = $method->getReturnType();
        $this->assertInstanceOf(ReflectionType::class, $returnType);
        $this->assertSame('string', $returnType->getName());
    }

    /**
     * Test interface namespace
     */
    public function testInterfaceNamespace(): void
    {
        // Arrange & Act
        $reflection = new ReflectionClass(AssetEventInterface::class);

        // Assert
        $this->assertSame('Maniaba\AssetConnect\Events', $reflection->getNamespaceName());
    }

    /**
     * Test that interface can be implemented
     */
    public function testInterfaceCanBeImplemented(): void
    {
        // Arrange
        $testEvent = new readonly class ($this->mockAsset) implements AssetEventInterface {
            public function __construct(private Asset $asset)
            {
            }

            #[Override]
            public function getAsset(): Asset
            {
                return $this->asset;
            }

            #[Override]
            public static function name(): string
            {
                return 'test.event';
            }
        };

        // Act & Assert
        $this->assertInstanceOf(AssetEventInterface::class, $testEvent);
        $this->assertSame($this->mockAsset, $testEvent->getAsset());
        $this->assertSame('test.event', $testEvent::name());
    }

    /**
     * Test interface method parameters
     */
    public function testGetAssetMethodParameters(): void
    {
        // Arrange & Act
        $reflection = new ReflectionClass(AssetEventInterface::class);
        $method     = $reflection->getMethod('getAsset');

        // Assert
        $this->assertCount(0, $method->getParameters());
    }

    /**
     * Test name method parameters
     */
    public function testNameMethodParameters(): void
    {
        // Arrange & Act
        $reflection = new ReflectionClass(AssetEventInterface::class);
        $method     = $reflection->getMethod('name');

        // Assert
        $this->assertCount(0, $method->getParameters());
    }
}
