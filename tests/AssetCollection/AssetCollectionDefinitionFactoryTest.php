<?php

declare(strict_types=1);

namespace Tests\AssetCollection;

use CodeIgniter\Test\CIUnitTestCase;
use Maniaba\FileConnect\Asset\Interfaces\AssetCollectionDefinitionInterface;
use Maniaba\FileConnect\AssetCollection\AssetCollectionDefinitionFactory;
use Maniaba\FileConnect\Exceptions\InvalidArgumentException;
use Override;
use PHPUnit\Framework\MockObject\MockObject;
use stdClass;

/**
 * @internal
 */
final class AssetCollectionDefinitionFactoryTest extends CIUnitTestCase
{
    /**
     * @var AssetCollectionDefinitionInterface&MockObject
     */
    private MockObject $mockCollectionDefinition;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockCollectionDefinition = $this->createMock(AssetCollectionDefinitionInterface::class);
    }

    /**
     * Test validateStringClass method with valid class name
     */
    public function testValidateStringClassWithValidClassName(): void
    {
        // Arrange
        $className = TestAssetCollectionDefinition::class;

        // Act & Assert - no exception should be thrown
        AssetCollectionDefinitionFactory::validateStringClass($className);
        $this->assertTrue(true); // Dummy assertion to avoid PHPUnit warning
    }

    /**
     * Test validateStringClass method with invalid class name
     */
    public function testValidateStringClassWithInvalidClassName(): void
    {
        // Arrange
        $invalidClassName = 'NonExistentClass';

        // Act & Assert
        $this->expectException(InvalidArgumentException::class);
        AssetCollectionDefinitionFactory::validateStringClass($invalidClassName);
    }

    /**
     * Test validateStringClass method with class that doesn't implement AssetCollectionDefinitionInterface
     */
    public function testValidateStringClassWithClassNotImplementingInterface(): void
    {
        // Arrange
        $invalidClass = stdClass::class;

        // Act & Assert
        $this->expectException(InvalidArgumentException::class);
        AssetCollectionDefinitionFactory::validateStringClass($invalidClass);
    }

    /**
     * Test validateStringClass method with instance of AssetCollectionDefinitionInterface
     */
    public function testValidateStringClassWithInterfaceInstance(): void
    {
        // Act & Assert - no exception should be thrown
        AssetCollectionDefinitionFactory::validateStringClass($this->mockCollectionDefinition);
        $this->assertTrue(true); // Dummy assertion to avoid PHPUnit warning
    }

    /**
     * Test create method with class name
     */
    public function testCreateWithClassName(): void
    {
        // Arrange
        $className = TestAssetCollectionDefinition::class;

        // Act
        $result = AssetCollectionDefinitionFactory::create($className);

        // Assert
        $this->assertInstanceOf(AssetCollectionDefinitionInterface::class, $result);
        $this->assertInstanceOf(TestAssetCollectionDefinition::class, $result);
    }

    /**
     * Test create method with class name and constructor arguments
     */
    public function testCreateWithClassNameAndArguments(): void
    {
        // Arrange
        $className = TestAssetCollectionDefinitionWithArgs::class;
        $arg1      = 'test';
        $arg2      = 123;

        // Act
        $result = AssetCollectionDefinitionFactory::create($className, $arg1, $arg2);

        // Assert
        $this->assertInstanceOf(AssetCollectionDefinitionInterface::class, $result);
        $this->assertInstanceOf(TestAssetCollectionDefinitionWithArgs::class, $result);
        $this->assertSame($arg1, $result->arg1);
        $this->assertSame($arg2, $result->arg2);
    }

    /**
     * Test create method with instance
     */
    public function testCreateWithInstance(): void
    {
        // Act
        $result = AssetCollectionDefinitionFactory::create($this->mockCollectionDefinition);

        // Assert
        $this->assertSame($this->mockCollectionDefinition, $result);
    }
}

/**
 * Test implementation of AssetCollectionDefinitionInterface for testing
 */
class TestAssetCollectionDefinition implements AssetCollectionDefinitionInterface
{
    public function definition($definition): void
    {
        // Do nothing
    }
}

/**
 * Test implementation of AssetCollectionDefinitionInterface with constructor arguments for testing
 */
class TestAssetCollectionDefinitionWithArgs implements AssetCollectionDefinitionInterface
{
    public function __construct(
        public readonly string $arg1,
        public readonly int $arg2,
    ) {
    }

    public function definition($definition): void
    {
        // Do nothing
    }
}
