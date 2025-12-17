<?php

declare(strict_types=1);

namespace Tests\Asset\Properties;

use CodeIgniter\Entity\Entity;
use CodeIgniter\Test\CIUnitTestCase;
use Maniaba\AssetConnect\Asset\Interfaces\AssetCollectionDefinitionInterface;
use Maniaba\AssetConnect\Asset\Interfaces\AuthorizableAssetCollectionDefinitionInterface;
use Maniaba\AssetConnect\Asset\Properties\StorageProperty;
use Override;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @internal
 */
final class BasicInfoPropertyTest extends CIUnitTestCase
{
    private StorageProperty $basicInfoProperty;

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

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->basicInfoProperty                    = new StorageProperty([]);
        $this->mockEntity                           = $this->createMock(Entity::class);
        $this->mockCollectionDefinition             = $this->createMock(AssetCollectionDefinitionInterface::class);
        $this->mockAuthorizableCollectionDefinition = $this->createMock(AuthorizableAssetCollectionDefinitionInterface::class);
    }

    /**
     * Test that getName returns the correct name
     */
    public function testGetNameReturnsCorrectName(): void
    {
        $this->assertSame('storage_info', StorageProperty::getName());
    }

    /**
     * Test that create returns an instance of BasicInfoProperty
     */
    public function testCreateReturnsInstanceOfBasicInfoProperty(): void
    {
        // Arrange
        $properties = ['storage_info' => ['key' => 'value']];

        // Act
        $result = StorageProperty::create($properties);

        // Assert
        $this->assertInstanceOf(StorageProperty::class, $result);
        $this->assertSame('value', $result->get('key'));
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
