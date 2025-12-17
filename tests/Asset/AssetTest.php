<?php

declare(strict_types=1);

namespace Tests\Asset;

use CodeIgniter\Config\Factories;
use CodeIgniter\Entity\Entity;
use CodeIgniter\Files\File;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;
use Maniaba\AssetConnect\Asset\Asset;
use Maniaba\AssetConnect\Asset\AssetMetadata;
use Maniaba\AssetConnect\Asset\Interfaces\AssetCollectionDefinitionInterface;
use Override;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\Support\Config\TestAssetConfig;
use Tests\Support\TestEntity;

/**
 * @internal
 */
final class AssetTest extends CIUnitTestCase
{
    private Asset $asset;

    /**
     * @var File&MockObject
     */
    private MockObject $mockFile;

    /**
     * @var Entity&MockObject
     */
    private MockObject $mockEntity;

    /**
     * @var AssetCollectionDefinitionInterface&MockObject
     */
    private MockObject $mockCollectionDefinition;

    #[Override]
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
        $mockFunctions['Maniaba\AssetConnect\AssetCollection\AssetCollectionDefinitionFactory::validateStringClass'] = static fn () => null;

        // For testCreateWithInvalidReturnType
        $mockFunctions['Maniaba\AssetConnect\Models\AssetModel::init'] = null;
    }

    /**
     * Test setting entity type with an Entity instance
     */
    public function testSetEntityTypeWithEntityInstance(): void
    {
        // Arrange
        $this->mockEntity                                       = $this->createMock(Entity::class);
        $config                                                 = config('Asset');
        $config->entityKeyDefinitions[$this->mockEntity::class] = 'mock_entity';

        // Act
        $result = $this->asset->setEntityType($this->mockEntity);

        // Assert
        $this->assertSame($this->asset, $result);
        $this->assertSame('mock_entity', $this->asset->entity_type);
        $this->assertSame($this->mockEntity::class, $this->asset->subject_entity_class, 'The subject_entity_class should be set to the correct class name.');
    }

    /**
     * Test setting entity type with a class name
     */
    public function testSetEntityTypeWithClassName(): void
    {
        // Arrange
        $entityClass = Entity::class;

        Factories::injectMock('config', \Maniaba\AssetConnect\Config\Asset::class, new TestAssetConfig());

        // Act
        $result = $this->asset->setEntityType($entityClass);

        // Assert
        $this->assertSame($this->asset, $result);
        $this->assertSame('basic_entity', $this->asset->entity_type);
        $this->assertSame($entityClass, $this->asset->subject_entity_class, 'The subject_entity_class should be set to the correct class name.');
    }

    public function testSetEntityTypeWithStringAliasName(): void
    {
        // Arrange
        $entityClass = TestEntity::class;

        Factories::injectMock('config', \Maniaba\AssetConnect\Config\Asset::class, new TestAssetConfig());

        // Act
        $result = $this->asset->setEntityType('test_entity');

        // Assert
        $this->assertSame($this->asset, $result);
        $this->assertSame('test_entity', $this->asset->entity_type, 'The entity_type should be set to the alias name.');
        $this->assertSame($entityClass, $this->asset->subject_entity_class, 'The subject_entity_class should be set to the correct class name.');
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
        $this->mockCollectionDefinition                                           = $this->createMock(AssetCollectionDefinitionInterface::class);
        $config                                                                   = config('Asset');
        $config->collectionKeyDefinitions[$this->mockCollectionDefinition::class] = 'mock_definition';
        // Act
        $result = $this->asset->setCollection($this->mockCollectionDefinition);

        // Assert
        $this->assertSame($this->asset, $result);
        $this->assertSame('mock_definition', $this->asset->collection);
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
        $propertiesObject = new AssetMetadata();

        $setMetadata = $this->getPrivateMethodInvoker($this->asset, 'setMetadata');
        $setMetadata($propertiesObject);

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
     * Test create method with data
     */
    public function testCreateWithData(): void
    {
        // Arrange
        $data = [
            'name'      => 'Test Asset',
            'file_name' => 'test.jpg',
        ];

        // Act
        $asset = Asset::create($data);

        /** @phpstan-ignore-next-line Assert */
        $this->assertInstanceOf(Asset::class, $asset);
        $this->assertSame('Test Asset', $asset->name);
        $this->assertSame('test.jpg', $asset->file_name);
    }

    /**
     * Test create method with null data
     */
    public function testCreateWithNullData(): void
    {
        // Act
        $asset = Asset::create();

        /** @phpstan-ignore-next-line Assert */
        $this->assertInstanceOf(Asset::class, $asset);
    }
}
