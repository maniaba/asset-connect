<?php

declare(strict_types=1);

namespace Tests\AssetCollection;

use CodeIgniter\Config\Factories;
use CodeIgniter\Model;
use CodeIgniter\Test\CIUnitTestCase;
use Config\App;
use Maniaba\AssetConnect\Asset\Interfaces\AssetCollectionDefinitionInterface;
use Maniaba\AssetConnect\AssetCollection\DefaultAssetCollection;
use Maniaba\AssetConnect\AssetCollection\SetupAssetCollection;
use Maniaba\AssetConnect\Config\Asset;
use Maniaba\AssetConnect\Exceptions\AssetException;
use Maniaba\AssetConnect\Exceptions\InvalidArgumentException;
use Maniaba\AssetConnect\PathGenerator\Interfaces\PathGeneratorInterface;
use PHPUnit\Framework\MockObject\Stub;
use stdClass;
use Tests\Support\Config\TestAssetConfig;
use Tests\Support\PathGenerators\TestPathGenerator;

/**
 * @internal
 */
final class SetupAssetCollectionTest extends CIUnitTestCase
{
    private SetupAssetCollection $setupAssetCollection;

    /**
     * @var Asset&Stub
     */
    private Stub $mockAssetConfig;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupAssetCollection = new SetupAssetCollection();
        $this->mockAssetConfig      = $this->createStub(Asset::class);
        // Setup global function mocks
        Factories::injectMock('config', 'Asset', $this->mockAssetConfig);
        Factories::injectMock('models', 'TestModel', $this->createStub(Model::class));
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

        // Get the collection definition using getPrivateProperty
        $collectionDefinition = $this->getPrivateProperty($this->setupAssetCollection, 'collectionDefinition');

        $this->assertInstanceOf(DefaultAssetCollection::class, $collectionDefinition);
    }

    /**
     * Test setDefaultCollectionDefinition with instance
     */
    public function testSetDefaultCollectionDefinitionWithInstance(): void
    {
        $assetCollectionStub = $this->createStub(AssetCollectionDefinitionInterface::class);
        // Act
        $result = $this->setupAssetCollection->setDefaultCollectionDefinition($assetCollectionStub);

        // Assert
        $this->assertSame($this->setupAssetCollection, $result);

        // Get the collection definition using getPrivateProperty
        $collectionDefinition = $this->getPrivateProperty($this->setupAssetCollection, 'collectionDefinition');

        $this->assertSame($assetCollectionStub, $collectionDefinition);
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

        // Get the path generator using getPrivateProperty
        $pathGenerator = $this->getPrivateProperty($this->setupAssetCollection, 'pathGenerator');

        $this->assertInstanceOf(TestPathGenerator::class, $pathGenerator);
    }

    /**
     * Test setPathGenerator with instance
     */
    public function testSetPathGeneratorWithInstance(): void
    {
        $pdfGeneratorStub = $this->createStub(PathGeneratorInterface::class);
        // Act
        $result = $this->setupAssetCollection->setPathGenerator($pdfGeneratorStub);

        // Assert
        $this->assertSame($this->setupAssetCollection, $result);

        // Get the path generator using getPrivateProperty
        $pathGenerator = $this->getPrivateProperty($this->setupAssetCollection, 'pathGenerator');

        $this->assertSame($pdfGeneratorStub, $pathGenerator);
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
        $pdbStub = $this->createStub(PathGeneratorInterface::class);
        // Arrange
        $this->setupAssetCollection->setPathGenerator($pdbStub);

        // Act
        $result = $this->setupAssetCollection->getPathGenerator();

        // Assert
        $this->assertSame($pdbStub, $result);
    }

    public function testGetPathGeneratorUsesConfiguredDefaultWhenNotSet(): void
    {
        $config                       = new TestAssetConfig();
        $config->defaultPathGenerator = TestPathGenerator::class;

        Factories::injectMock('config', 'Asset', $config);

        try {
            $setupAssetCollection = new SetupAssetCollection();

            $pathGenerator = $setupAssetCollection->getPathGenerator();

            $this->assertInstanceOf(TestPathGenerator::class, $pathGenerator);
            $this->assertSame($pathGenerator, $setupAssetCollection->getPathGenerator(), 'Default path generator should be cached after first resolution.');
        } finally {
            Factories::reset('config');
        }
    }

    public function testGetPathGeneratorThrowsWhenConfiguredDefaultIsInvalid(): void
    {
        $config = new TestAssetConfig();
        $this->setPrivateProperty($config, 'defaultPathGenerator', stdClass::class);

        Factories::injectMock('config', 'Asset', $config);

        try {
            $setupAssetCollection = new SetupAssetCollection();

            $setupAssetCollection->getPathGenerator();
        } catch (InvalidArgumentException $exception) {
            $this->assertSame(
                sprintf(
                    'Default path generator class %s does not exist or does not implement %s.',
                    stdClass::class,
                    PathGeneratorInterface::class,
                ),
                $exception->getMessage(),
                'Invalid configured default path generator should expose the exact configuration error.',
            );

            return;
        } finally {
            Factories::reset('config');
        }

        $this->fail('Expected invalid default path generator configuration to throw.');
    }

    /**
     * Test getCollectionDefinition when collection definition is set
     */
    public function testGetCollectionDefinitionWhenSet(): void
    {
        $assetCollectionStub = $this->createStub(AssetCollectionDefinitionInterface::class);
        // Arrange
        $this->setupAssetCollection->setDefaultCollectionDefinition($assetCollectionStub);

        // Act
        $result = $this->setupAssetCollection->getCollectionDefinition();

        // Assert
        $this->assertSame($assetCollectionStub, $result);
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

    public function testDefaultSanitizerUsesPermittedUriChars(): void
    {
        $sanitizer = $this->setupAssetCollection->getFileNameSanitizer();
        $result    = $sanitizer('1757885128897-Living-&-Dining-Room-20250915-214045.jpg');

        $this->assertSame('1757885128897-Living-Dining-Room-20250915-214045.jpg', $result);
        $this->assertMatchesRegularExpression(
            '/\A[' . config('App')->permittedURIChars . ']+\z/iu',
            urldecode($result),
        );
    }

    public function testDefaultSanitizerUsesConfiguredPermittedUriChars(): void
    {
        $appConfig = new class () extends App {
            public string $permittedURIChars = 'a-z0-9._';
        };
        Factories::injectMock('config', 'App', $appConfig);

        $sanitizer = $this->setupAssetCollection->getFileNameSanitizer();
        $result    = $sanitizer('Upload.File-&-Final.JPG');

        $this->assertSame('Upload.File_Final.JPG', $result);
        $this->assertMatchesRegularExpression(
            '/\A[' . $appConfig->permittedURIChars . ']+\z/iu',
            urldecode($result),
        );
    }

    public function testDefaultSanitizerHandlesEncodedDisallowedUriChars(): void
    {
        $sanitizer = $this->setupAssetCollection->getFileNameSanitizer();
        $result    = $sanitizer('encoded-%26-name.jpg');

        $this->assertSame('encoded-name.jpg', $result);
        $this->assertMatchesRegularExpression(
            '/\A[' . config('App')->permittedURIChars . ']+\z/iu',
            urldecode($result),
        );
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

    public function testConstructorThrowsWhenAssetConfigIsInvalid(): void
    {
        Factories::injectMock('config', 'Asset', new stdClass());

        try {
            new SetupAssetCollection();
        } catch (InvalidArgumentException $exception) {
            $this->assertSame(
                ['Asset configuration is not properly set up.'],
                $exception->errors,
                'SetupAssetCollection should reject non-Asset config instances.',
            );

            return;
        } finally {
            Factories::reset('config');
        }

        $this->fail('Expected invalid Asset config to throw.');
    }
}
