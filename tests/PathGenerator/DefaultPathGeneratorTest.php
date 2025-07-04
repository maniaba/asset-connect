<?php

declare(strict_types=1);

namespace Tests\PathGenerator;

use CodeIgniter\Test\CIUnitTestCase;
use Maniaba\FileConnect\Asset\Interfaces\AssetCollectionGetterInterface;
use Maniaba\FileConnect\PathGenerator\DefaultPathGenerator;
use Maniaba\FileConnect\PathGenerator\PathGeneratorHelper;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;

/**
 * @internal
 */
final class DefaultPathGeneratorTest extends CIUnitTestCase
{
    private DefaultPathGenerator $pathGenerator;
    private PathGeneratorHelper $helper;
    private AssetCollectionGetterInterface|MockObject $mockCollection;

    protected function setUp(): void
    {
        parent::setUp();

        // Create the path generator
        $this->pathGenerator = new DefaultPathGenerator();

        // Create a real helper and mock collection
        $this->helper         = new PathGeneratorHelper();
        $this->mockCollection = $this->createMock(AssetCollectionGetterInterface::class);

        // Mock global functions
        global $mockFunctions;
        $mockFunctions['date'] = static function ($format) {
            if ($format === 'Y-m-d') {
                return '2023-01-01';
            }
            if ($format === 'His.U') {
                return '120000.000000';
            }

            return '';
        };
    }

    /**
     * Test getStoreDirectory method with protected collection
     */
    public function testGetStoreDirectoryWithProtectedCollection(): void
    {
        // Arrange
        $this->mockCollection->method('isProtected')->willReturn(true);

        // Define WRITEPATH constant if not defined
        if (! defined('WRITEPATH')) {
            define('WRITEPATH', 'writable/');
        }

        // Act
        $storeDirectory = $this->pathGenerator->getStoreDirectory($this->helper, $this->mockCollection);

        // Assert
        $this->assertSame(WRITEPATH, $storeDirectory);
    }



    /**
     * Test getPath method
     */
    public function testGetPath(): void
    {
        // Arrange
        $storeDirectory   = '/root/public/';
        $fileRelativePath = 'assets/2023-01-01/120000.000000/';

        // Use reflection to set the private properties
        $reflectionClass = new ReflectionClass(DefaultPathGenerator::class);

        $storeDirectoryProperty = $reflectionClass->getProperty('storeDirectory');
        $storeDirectoryProperty->setAccessible(true);
        $storeDirectoryProperty->setValue($this->pathGenerator, $storeDirectory);

        $fileRelativePathProperty = $reflectionClass->getProperty('fileRelativePath');
        $fileRelativePathProperty->setAccessible(true);
        $fileRelativePathProperty->setValue($this->pathGenerator, $fileRelativePath);

        // Act
        $path = $this->pathGenerator->getPath($this->helper, $this->mockCollection);

        // Assert
        $this->assertSame($storeDirectory . $fileRelativePath, $path);
    }

    /**
     * Test getStoreDirectoryForVariants method
     */
    public function testGetStoreDirectoryForVariants(): void
    {
        // Arrange
        $storeDirectory = '/root/public/';

        // Use reflection to set the private property
        $reflectionClass = new ReflectionClass(DefaultPathGenerator::class);

        $storeDirectoryProperty = $reflectionClass->getProperty('storeDirectory');
        $storeDirectoryProperty->setAccessible(true);
        $storeDirectoryProperty->setValue($this->pathGenerator, $storeDirectory);

        // Act
        $storeDirectoryForVariants = $this->pathGenerator->getStoreDirectoryForVariants($this->helper, $this->mockCollection);

        // Assert
        $this->assertSame($storeDirectory, $storeDirectoryForVariants);
    }

    /**
     * Test getFileRelativePathForVariants method
     */
    public function testGetFileRelativePathForVariants(): void
    {
        // Arrange
        $fileRelativePath = 'assets/2023-01-01/120000.000000/';

        // Use reflection to set the private property
        $reflectionClass = new ReflectionClass(DefaultPathGenerator::class);

        $fileRelativePathProperty = $reflectionClass->getProperty('fileRelativePath');
        $fileRelativePathProperty->setAccessible(true);
        $fileRelativePathProperty->setValue($this->pathGenerator, $fileRelativePath);

        // Act
        $fileRelativePathForVariants = $this->pathGenerator->getFileRelativePathForVariants($this->helper, $this->mockCollection);

        // Assert
        $expectedPath = $fileRelativePath . 'variants' . DIRECTORY_SEPARATOR;
        $this->assertSame($expectedPath, $fileRelativePathForVariants);
    }

    /**
     * Test getPathForVariants method
     */
    public function testGetPathForVariants(): void
    {
        // This test verifies that getPathForVariants combines the store directory and file relative path correctly

        // Create a new DefaultPathGenerator for this test to avoid interference
        $pathGenerator = new DefaultPathGenerator();

        // Mock the collection to return consistent values
        $mockCollection = $this->createMock(AssetCollectionGetterInterface::class);
        $mockCollection->method('isProtected')->willReturn(false);

        // Mock realpath to return a fixed path
        global $mockFunctions;
        $mockFunctions['realpath'] = static fn ($path) => '/root/public';

        // First, get the store directory
        $storeDirectory = $pathGenerator->getStoreDirectory($this->helper, $mockCollection);

        // Then, get the file relative path for variants
        // We need to set the fileRelativePath property first
        $reflectionClass          = new ReflectionClass(DefaultPathGenerator::class);
        $fileRelativePathProperty = $reflectionClass->getProperty('fileRelativePath');
        $fileRelativePathProperty->setAccessible(true);
        $fileRelativePathProperty->setValue($pathGenerator, 'assets/2023-01-01/120000.000000/');

        $fileRelativePathForVariants = $pathGenerator->getFileRelativePathForVariants($this->helper, $mockCollection);

        // Now test getPathForVariants
        $path = $pathGenerator->getPathForVariants($this->helper, $mockCollection);

        // Assert that the path is the combination of store directory and file relative path for variants
        $this->assertSame($storeDirectory . $fileRelativePathForVariants, $path);
    }
}
