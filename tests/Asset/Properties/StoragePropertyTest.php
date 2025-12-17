<?php

declare(strict_types=1);

namespace Tests\Asset\Properties;

use CodeIgniter\Test\CIUnitTestCase;
use Maniaba\AssetConnect\Asset\Properties\StorageProperty;
use Override;

/**
 * @internal
 */
final class StoragePropertyTest extends CIUnitTestCase
{
    private StorageProperty $storageProperty;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->storageProperty = new StorageProperty([]);
    }

    /**
     * Test that getName returns the correct name
     */
    public function testGetNameReturnsCorrectName(): void
    {
        $this->assertSame('storage_info', StorageProperty::getName());
    }

    /**
     * Test that create returns an instance of StorageProperty
     */
    public function testCreateReturnsInstanceOfStorageProperty(): void
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
        $this->storageProperty->setStorageBaseDirectoryPath($path);

        // Assert
        $this->assertSame($path, $this->storageProperty->storageBaseDirectoryPath());
    }

    /**
     * Test setFileRelativePath and fileRelativePath
     */
    public function testSetAndGetFileRelativePath(): void
    {
        // Arrange
        $path = 'relative/path/to/file';

        // Act
        $this->storageProperty->setFileRelativePath($path);

        // Assert
        $this->assertSame($path, $this->storageProperty->fileRelativePath());
    }
}
