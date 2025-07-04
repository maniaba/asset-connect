<?php

declare(strict_types=1);

namespace Tests\AssetCollection;

use CodeIgniter\Config\Factories;
use CodeIgniter\Model;
use CodeIgniter\Test\CIUnitTestCase;
use Maniaba\FileConnect\Asset\Interfaces\AssetCollectionDefinitionInterface;
use Maniaba\FileConnect\AssetCollection\DefaultAssetCollection;
use Maniaba\FileConnect\AssetCollection\SetupAssetCollection;
use Maniaba\FileConnect\Config\Asset;
use Maniaba\FileConnect\Exceptions\AssetException;
use Maniaba\FileConnect\Exceptions\InvalidArgumentException;
use Maniaba\FileConnect\PathGenerator\Interfaces\PathGeneratorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;
use stdClass;

/**
 * @internal
 */
final class SetupAssetCollectionTest extends CIUnitTestCase
{
    private SetupAssetCollection $setupAssetCollection;

    /**
     * @var AssetCollectionDefinitionInterface&MockObject
     */
    private MockObject $mockCollectionDefinition;

    /**
     * @var MockObject&PathGeneratorInterface
     */
    private MockObject $mockPathGenerator;

    /**
     * @var Asset&MockObject
     */
    private MockObject $mockAssetConfig;

    /**
     * @var MockObject&Model
     */
    private MockObject $mockModel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setupAssetCollection     = new SetupAssetCollection();
        $this->mockCollectionDefinition = $this->createMock(AssetCollectionDefinitionInterface::class);
        $this->mockPathGenerator        = $this->createMock(PathGeneratorInterface::class);
        $this->mockAssetConfig          = $this->createMock(Asset::class);
        $this->mockModel                = $this->createMock(Model::class);

        // Setup global function mocks
        Factories::injectMock('config', 'Asset', $this->mockAssetConfig);
        Factories::injectMock('models', 'TestModel', $this->mockModel);
    }

    /**
     * Test setDefaultCollectionDefinition with class name
     */
    public function testSetDefaultCollectionDefinitionWithClassName(): void
    {
        // Arrange
        $className = DefaultAssetCollection::class;

        // Act
        $result = $this->setupAssetCollection->setDefaultCollectionDefinition($className);

        // Assert
        $this->assertSame($this->setupAssetCollection, $result);

        // Get the collection definition using reflection
        $reflection = new ReflectionClass($this->setupAssetCollection);
        $property   = $reflection->getProperty('collectionDefinition');
        $property->setAccessible(true);
        $collectionDefinition = $property->getValue($this->setupAssetCollection);

        $this->assertInstanceOf(DefaultAssetCollection::class, $collectionDefinition);
    }

    /**
     * Test setDefaultCollectionDefinition with instance
     */
    public function testSetDefaultCollectionDefinitionWithInstance(): void
    {
        // Act
        $result = $this->setupAssetCollection->setDefaultCollectionDefinition($this->mockCollectionDefinition);

        // Assert
        $this->assertSame($this->setupAssetCollection, $result);

        // Get the collection definition using reflection
        $reflection = new ReflectionClass($this->setupAssetCollection);
        $property   = $reflection->getProperty('collectionDefinition');
        $property->setAccessible(true);
        $collectionDefinition = $property->getValue($this->setupAssetCollection);

        $this->assertSame($this->mockCollectionDefinition, $collectionDefinition);
    }

    /**
     * Test setPathGenerator with class name
     */
    public function testSetPathGeneratorWithClassName(): void
    {
        // Arrange
        $className = TestPathGenerator::class;

        // Act
        $result = $this->setupAssetCollection->setPathGenerator($className);

        // Assert
        $this->assertSame($this->setupAssetCollection, $result);

        // Get the path generator using reflection
        $reflection = new ReflectionClass($this->setupAssetCollection);
        $property   = $reflection->getProperty('pathGenerator');
        $property->setAccessible(true);
        $pathGenerator = $property->getValue($this->setupAssetCollection);

        $this->assertInstanceOf(TestPathGenerator::class, $pathGenerator);
    }

    /**
     * Test setPathGenerator with instance
     */
    public function testSetPathGeneratorWithInstance(): void
    {
        // Act
        $result = $this->setupAssetCollection->setPathGenerator($this->mockPathGenerator);

        // Assert
        $this->assertSame($this->setupAssetCollection, $result);

        // Get the path generator using reflection
        $reflection = new ReflectionClass($this->setupAssetCollection);
        $property   = $reflection->getProperty('pathGenerator');
        $property->setAccessible(true);
        $pathGenerator = $property->getValue($this->setupAssetCollection);

        $this->assertSame($this->mockPathGenerator, $pathGenerator);
    }

    /**
     * Test setPathGenerator with invalid class name
     */
    public function testSetPathGeneratorWithInvalidClassName(): void
    {
        // Arrange
        $invalidClassName = 'NonExistentClass';

        // Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->setupAssetCollection->setPathGenerator($invalidClassName);
    }

    /**
     * Test setPathGenerator with class that doesn't implement PathGeneratorInterface
     */
    public function testSetPathGeneratorWithClassNotImplementingInterface(): void
    {
        // Arrange
        $invalidClass = stdClass::class;

        // Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->setupAssetCollection->setPathGenerator($invalidClass);
    }

    /**
     * Test getPathGenerator when path generator is set
     */
    public function testGetPathGeneratorWhenSet(): void
    {
        // Arrange
        $this->setupAssetCollection->setPathGenerator($this->mockPathGenerator);

        // Act
        $result = $this->setupAssetCollection->getPathGenerator();

        // Assert
        $this->assertSame($this->mockPathGenerator, $result);
    }

    /**
     * Test getCollectionDefinition when collection definition is set
     */
    public function testGetCollectionDefinitionWhenSet(): void
    {
        // Arrange
        $this->setupAssetCollection->setDefaultCollectionDefinition($this->mockCollectionDefinition);

        // Act
        $result = $this->setupAssetCollection->getCollectionDefinition();

        // Assert
        $this->assertSame($this->mockCollectionDefinition, $result);
    }

    /**
     * Test getCollectionDefinition when collection definition is not set
     */
    public function testGetCollectionDefinitionWhenNotSet(): void
    {
        // Arrange
        $this->mockAssetConfig->defaultCollection = DefaultAssetCollection::class;

        // Act
        $result = $this->setupAssetCollection->getCollectionDefinition();

        // Assert
        $this->assertInstanceOf(DefaultAssetCollection::class, $result);
    }

    /**
     * Test setFileNameSanitizer and getFileNameSanitizer
     */
    public function testSetAndGetFileNameSanitizer(): void
    {
        // Arrange
        $sanitizer = static fn (string $fileName): string => 'sanitized_' . $fileName;

        // Act
        $result          = $this->setupAssetCollection->setFileNameSanitizer($sanitizer);
        $sanitizerResult = $this->setupAssetCollection->getFileNameSanitizer()('test.jpg');

        // Assert
        $this->assertSame($this->setupAssetCollection, $result);
        $this->assertSame('sanitized_test.jpg', $sanitizerResult);
    }

    /**
     * Test getFileNameSanitizer returns default sanitizer when not set
     */
    public function testGetFileNameSanitizerReturnsDefaultSanitizerWhenNotSet(): void
    {
        // Act
        $sanitizer = $this->setupAssetCollection->getFileNameSanitizer();
        $result    = $sanitizer('test#file.jpg');

        // Assert
        $this->assertSame('test-file.jpg', $result);
    }

    /**
     * Test default sanitizer throws exception for PHP files
     */
    public function testDefaultSanitizerThrowsExceptionForPhpFiles(): void
    {
        // Arrange
        $sanitizer = $this->setupAssetCollection->getFileNameSanitizer();

        // Act & Assert
        $this->expectException(AssetException::class);
        $sanitizer('malicious.php');
    }

    /**
     * Test setPreserveOriginal and shouldPreserveOriginal
     */
    public function testSetAndShouldPreserveOriginal(): void
    {
        // Act
        $result         = $this->setupAssetCollection->setPreserveOriginal(true);
        $preserveResult = $this->setupAssetCollection->shouldPreserveOriginal();

        // Assert
        $this->assertSame($this->setupAssetCollection, $result);
        $this->assertTrue($preserveResult);
    }

    /**
     * Test setSubjectPrimaryKeyAttribute and getSubjectPrimaryKeyAttribute
     */
    public function testSetAndGetSubjectPrimaryKeyAttribute(): void
    {
        // Arrange
        $attribute = 'user_id';

        // Act
        $result          = $this->setupAssetCollection->setSubjectPrimaryKeyAttribute($attribute);
        $attributeResult = $this->setupAssetCollection->getSubjectPrimaryKeyAttribute();

        // Assert
        $this->assertSame($this->setupAssetCollection, $result);
        $this->assertSame($attribute, $attributeResult);
    }

    /**
     * Test autoDetectSubjectPrimaryKeyAttribute with valid model
     */
    public function testAutoDetectSubjectPrimaryKeyAttributeWithValidModel(): void
    {
        $model = new class () extends Model {
            protected $primaryKey = 'test_id';
        };
        // Arrange
        $modelClass = $model::class;

        // Act
        $result          = $this->setupAssetCollection->autoDetectSubjectPrimaryKeyAttribute($modelClass);
        $attributeResult = $this->setupAssetCollection->getSubjectPrimaryKeyAttribute();

        // Assert
        $this->assertSame($this->setupAssetCollection, $result);
        $this->assertSame('test_id', $attributeResult);
    }

    /**
     * Test autoDetectSubjectPrimaryKeyAttribute with invalid model class
     */
    public function testAutoDetectSubjectPrimaryKeyAttributeWithInvalidModelClass(): void
    {
        // Arrange
        $invalidModelClass = 'NonExistentModel';

        // Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->setupAssetCollection->autoDetectSubjectPrimaryKeyAttribute($invalidModelClass);
    }
}

/**
 * Test implementation of PathGeneratorInterface for testing
 */
class TestPathGenerator implements PathGeneratorInterface
{
    public function getStoreDirectory($generatorHelper, $collection): string
    {
        return '/path/to/store/';
    }

    public function getFileRelativePath($generatorHelper, $collection): string
    {
        return 'relative/path/';
    }

    public function getPath($generatorHelper, $collection): string
    {
        return '/path/to/store/relative/path/';
    }

    public function getStoreDirectoryForVariants($generatorHelper, $collection): string
    {
        return '/path/to/store/variants/';
    }

    public function getFileRelativePathForVariants($generatorHelper, $collection): string
    {
        return 'relative/path/variants/';
    }

    public function getPathForVariants($generatorHelper, $collection): string
    {
        return '/path/to/store/variants/relative/path/variants/';
    }

    public function onCreatedDirectory(string $path): void
    {
        // Do nothing
    }
}
