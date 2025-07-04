<?php

declare(strict_types=1);

namespace Tests\Asset;

use CodeIgniter\Entity\Entity;
use CodeIgniter\Files\File;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;
use Maniaba\FileConnect\Asset\Asset;
use Maniaba\FileConnect\Asset\AssetMetadata;
use Maniaba\FileConnect\Asset\Interfaces\AssetCollectionDefinitionInterface;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @internal
 */
final class AssetTest extends CIUnitTestCase
{
    private Asset $asset;
    private MockObject $mockFile;
    private MockObject $mockEntity;
    private MockObject $mockCollectionDefinition;

    protected function setUp(): void
    {
        parent::setUp();

        $this->asset                    = new Asset();
        $this->mockFile                 = $this->createMock(File::class);
        $this->mockEntity               = $this->createMock(Entity::class);
        $this->mockCollectionDefinition = $this->createMock(AssetCollectionDefinitionInterface::class);

        // Setup global function mocks
        $this->setupGlobalFunctionMocks();
    }

    /**
     * Setup global function mocks
     */
    private function setupGlobalFunctionMocks(): void
    {
        // Mock AssetCollectionDefinitionFactory::validateStringClass
        global $mockFunctions;
        $mockFunctions['Maniaba\FileConnect\AssetCollection\AssetCollectionDefinitionFactory::validateStringClass'] = static fn () => null;
    }

    /**
     * Test setting entity type with an Entity instance
     */
    public function testSetEntityTypeWithEntityInstance(): void
    {
        // Arrange
        $this->mockEntity = $this->getMockBuilder(Entity::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Act
        $result = $this->asset->setEntityType($this->mockEntity);

        // Assert
        $this->assertSame($this->asset, $result);
        $this->assertSame(md5($this->mockEntity::class), $this->asset->entity_type);
    }

    /**
     * Test setting entity type with a class name
     */
    public function testSetEntityTypeWithClassName(): void
    {
        // Arrange
        $entityClass = Entity::class;

        // Act
        $result = $this->asset->setEntityType($entityClass);

        // Assert
        $this->assertSame($this->asset, $result);
        $this->assertSame(md5($entityClass), $this->asset->entity_type);
    }

    /**
     * Test setting entity type with an invalid class name
     */
    public function testSetEntityTypeWithInvalidClassName(): void
    {
        // Arrange
        $invalidClass = 'InvalidClass';

        // Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->asset->setEntityType($invalidClass);
    }

    /**
     * Test setting collection with an AssetCollectionDefinitionInterface instance
     */
    public function testSetCollectionWithInterfaceInstance(): void
    {
        // Arrange
        $this->mockCollectionDefinition = $this->createMock(AssetCollectionDefinitionInterface::class);

        // Act
        $result = $this->asset->setCollection($this->mockCollectionDefinition);

        // Assert
        $this->assertSame($this->asset, $result);
        $this->assertSame(md5($this->mockCollectionDefinition::class), $this->asset->collection);
    }

    /**
     * Test setting collection with a class name
     */
    public function testSetCollectionWithClassName(): void
    {
        // Arrange
        $collectionClass = 'ValidCollectionClass';

        // Mock the validateStringClass method
        global $mockFunctions;
        $mockFunctions['Maniaba\FileConnect\AssetCollection\AssetCollectionDefinitionFactory::validateStringClass'] = function ($class) use ($collectionClass) {
            $this->assertSame($collectionClass, $class);

            return null;
        };

        // Act
        $result = $this->asset->setCollection($collectionClass);

        // Assert
        $this->assertSame($this->asset, $result);
        $this->assertSame(md5($collectionClass), $this->asset->collection);
    }

    /**
     * Test getting properties when they are not set
     */
    public function testGetPropertiesWhenNotSet(): void
    {
        // Act
        $properties = $this->asset->metadata;

        // Assert
        $this->assertNotNull($properties);
    }

    /**
     * Test getting properties when they are set as a Properties object
     */
    public function testGetPropertiesWhenSetAsPropertiesObject(): void
    {
        // Create a Properties object with the JSON string
        $properties = new AssetMetadata([
            'key' => 'value',
        ]);

        $setMetadata = $this->getPrivateMethodInvoker($this->asset, 'setMetadata');
        $setMetadata($properties);

        // Act
        $properties = $this->asset->metadata;

        // Assert
        $this->assertInstanceOf(AssetMetadata::class, $properties);
    }

    /**
     * Test getting properties when they are set as a Properties object
     */
    public function testGetPropertiesWhenSetAsObject(): void
    {
        // Arrange
        $propertiesObject      = new AssetMetadata();
        $this->asset->metadata = $propertiesObject;

        // Act
        $properties = $this->asset->metadata;

        // Assert
        $this->assertSame($propertiesObject, $properties);
    }

    /**
     * Test getting extension
     */
    public function testGetExtension(): void
    {
        // Arrange
        $this->mockFile->method('getExtension')
            ->willReturn('jpg');
        // @phpstan-ignore-next-line
        $this->asset->file = $this->mockFile;

        // Act
        $extension = $this->asset->extension;

        // Assert
        $this->assertSame('jpg', $extension);
    }

    /**
     * Test getting path dirname when path is set
     */
    public function testGetPathDirnameWhenPathIsSet(): void
    {
        // Arrange
        $path              = '/path/to/file.jpg';
        $this->asset->path = $path;

        // Act
        $dirname = $this->asset->path_dirname;

        // Assert
        $this->assertSame(dirname($path) . DIRECTORY_SEPARATOR, $dirname);
    }

    /**
     * Test getting path dirname when path is not set
     */
    public function testGetPathDirnameWhenPathIsNotSet(): void
    {
        // Act & Assert
        $this->expectException(\Maniaba\FileConnect\Exceptions\InvalidArgumentException::class);
    }
}
