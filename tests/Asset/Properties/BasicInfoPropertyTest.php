<?php

declare(strict_types=1);

namespace Tests\Asset\Properties;

use CodeIgniter\Entity\Entity;
use CodeIgniter\Test\CIUnitTestCase;
use Maniaba\FileConnect\Asset\Interfaces\AssetCollectionDefinitionInterface;
use Maniaba\FileConnect\Asset\Interfaces\AuthorizableAssetCollectionDefinitionInterface;
use Maniaba\FileConnect\Asset\Properties\BasicInfoProperty;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @internal
 */
final class BasicInfoPropertyTest extends CIUnitTestCase
{
    private BasicInfoProperty $basicInfoProperty;

    /**
     * @var Entity&MockObject
     */
    private MockObject $mockEntity;

    /**
     * @var AssetCollectionDefinitionInterface&MockObject
     */
    private MockObject $mockCollectionDefinition;

    /**
     * @var AuthorizableAssetCollectionDefinitionInterface&MockObject
     */
    private MockObject $mockAuthorizableCollectionDefinition;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basicInfoProperty                    = new BasicInfoProperty([]);
        $this->mockEntity                           = $this->createMock(Entity::class);
        $this->mockCollectionDefinition             = $this->createMock(AssetCollectionDefinitionInterface::class);
        $this->mockAuthorizableCollectionDefinition = $this->createMock(AuthorizableAssetCollectionDefinitionInterface::class);
    }

    /**
     * Test that getName returns the correct name
     */
    public function testGetNameReturnsCorrectName(): void
    {
        $this->assertSame('basic_info', BasicInfoProperty::getName());
    }

    /**
     * Test that create returns an instance of BasicInfoProperty
     */
    public function testCreateReturnsInstanceOfBasicInfoProperty(): void
    {
        // Arrange
        $properties = ['basic_info' => ['key' => 'value']];

        // Act
        $result = BasicInfoProperty::create($properties);

        // Assert
        $this->assertInstanceOf(BasicInfoProperty::class, $result);
        $this->assertSame('value', $result->get('key'));
    }

    /**
     * Test setEntityTypeClass with Entity instance
     */
    public function testSetEntityTypeClassWithEntityInstance(): void
    {
        // Arrange
        $entityClass = $this->mockEntity::class;

        // Act
        $this->basicInfoProperty->setEntityTypeClass($this->mockEntity);

        // Assert
        $this->assertSame($entityClass, $this->basicInfoProperty->entityTypeClassName());
    }

    /**
     * Test setEntityTypeClass with class name
     */
    public function testSetEntityTypeClassWithClassName(): void
    {
        // Arrange
        $entityClass = Entity::class;

        // Act
        $this->basicInfoProperty->setEntityTypeClass($entityClass);

        // Assert
        $this->assertSame($entityClass, $this->basicInfoProperty->entityTypeClassName());
    }

    /**
     * Test setCollectionClass with AssetCollectionDefinitionInterface instance
     */
    public function testSetCollectionClassWithInterfaceInstance(): void
    {
        // Arrange
        $collectionClass = $this->mockCollectionDefinition::class;

        // Act
        $this->basicInfoProperty->setCollectionClass($this->mockCollectionDefinition);

        // Assert
        $this->assertSame($collectionClass, $this->basicInfoProperty->collectionClassName());
    }

    /**
     * Test setCollectionClass with class name
     */
    public function testSetCollectionClassWithClassName(): void
    {
        // Arrange
        $collectionClass = AssetCollectionDefinitionInterface::class;

        // Act
        $this->basicInfoProperty->setCollectionClass($collectionClass);

        // Assert
        $this->assertSame($collectionClass, $this->basicInfoProperty->collectionClassName());
    }

    /**
     * Test isProtectedCollection returns true for AuthorizableAssetCollectionDefinitionInterface
     */
    public function testIsProtectedCollectionReturnsTrueForAuthorizableCollection(): void
    {
        // Arrange
        $this->basicInfoProperty->setCollectionClass($this->mockAuthorizableCollectionDefinition);

        // Act
        $result = $this->basicInfoProperty->isProtectedCollection();

        // Assert
        $this->assertTrue($result);
    }

    /**
     * Test isProtectedCollection returns false for non-authorizable collection
     */
    public function testIsProtectedCollectionReturnsFalseForNonAuthorizableCollection(): void
    {
        // Arrange
        $this->basicInfoProperty->setCollectionClass($this->mockCollectionDefinition);

        // Act
        $result = $this->basicInfoProperty->isProtectedCollection();

        // Assert
        $this->assertFalse($result);
    }

    /**
     * Test setStorageBaseDirectoryPath and storageBaseDirectoryPath
     */
    public function testSetAndGetStorageBaseDirectoryPath(): void
    {
        // Arrange
        $path = '/path/to/storage';

        // Act
        $this->basicInfoProperty->setStorageBaseDirectoryPath($path);

        // Assert
        $this->assertSame($path, $this->basicInfoProperty->storageBaseDirectoryPath());
    }

    /**
     * Test setFileRelativePath and fileRelativePath
     */
    public function testSetAndGetFileRelativePath(): void
    {
        // Arrange
        $path = 'relative/path/to/file';

        // Act
        $this->basicInfoProperty->setFileRelativePath($path);

        // Assert
        $this->assertSame($path, $this->basicInfoProperty->fileRelativePath());
    }
}
