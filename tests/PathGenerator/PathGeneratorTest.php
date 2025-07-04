<?php

declare(strict_types=1);

namespace Tests\PathGenerator;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Maniaba\FileConnect\AssetCollection\AssetCollection;
use Maniaba\FileConnect\PathGenerator\Interfaces\PathGeneratorInterface;
use Maniaba\FileConnect\PathGenerator\PathGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;

/**
 * @internal
 */
final class PathGeneratorTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    private PathGenerator $pathGenerator;
    private MockObject|PathGeneratorInterface $mockPathGeneratorInterface;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a real AssetCollection instance using reflection
        $reflectionClass = new ReflectionClass(AssetCollection::class);
        $assetCollection = $reflectionClass->newInstanceWithoutConstructor();

        // Create a mock PathGeneratorInterface
        $this->mockPathGeneratorInterface = $this->createMock(PathGeneratorInterface::class);

        // Use reflection to set the private property in AssetCollection
        $reflectionProperty = $reflectionClass->getProperty('pathGenerator');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($assetCollection, $this->mockPathGeneratorInterface);

        // Create the PathGenerator
        $this->pathGenerator = new PathGenerator($assetCollection);

        // Setup global function mocks
        $this->setupGlobalFunctionMocks();
    }

    /**
     * Setup global function mocks
     */
    private function setupGlobalFunctionMocks(): void
    {
        // Mock file system functions
        $this->setGlobalFunction('is_dir', static fn () => true);
        $this->setGlobalFunction('mkdir', static fn () => true);
        $this->setGlobalFunction('is_writable', static fn () => true);
        $this->setGlobalFunction('is_readable', static fn () => true);
    }

    /**
     * Set a global function mock
     */
    private function setGlobalFunction(string $name, callable $callback): void
    {
        global $mockFunctions;
        $mockFunctions[$name] = $callback;
    }

    /**
     * Test getStoreDirectory method
     */
    public function testGetStoreDirectory(): void
    {
        // Arrange
        $storeDirectory = HOMEPATH . '/build/path/to/store';

        // Setup expectations for the mock
        $this->mockPathGeneratorInterface->expects($this->once())
            ->method('getStoreDirectory')
            ->willReturn($storeDirectory);

        // Mock is_dir to return true for this specific path
        $this->setGlobalFunction('is_dir', static fn ($path) => $path === $storeDirectory);

        // Act
        $result = $this->pathGenerator->getStoreDirectory();

        // Assert
        $this->assertSame($storeDirectory, $result);
    }

    /**
     * Test getStoreDirectory method when directory doesn't exist
     */
    public function testGetStoreDirectoryWhenDirectoryDoesntExist(): void
    {
        // Arrange
        $storeDirectory = HOMEPATH . '/build/path/to/store';

        // Setup expectations for the mock
        $this->mockPathGeneratorInterface->expects($this->once())
            ->method('getStoreDirectory')
            ->willReturn($storeDirectory);

        // Mock is_dir to return false
        $this->setGlobalFunction('is_dir', static fn ($path) => false);

        // Mock mkdir to return true and verify parameters
        $this->setGlobalFunction('mkdir', function ($path, $mode, $recursive) use ($storeDirectory) {
            $this->assertSame($storeDirectory, $path);
            $this->assertSame(0755, $mode);
            $this->assertTrue($recursive);

            return true;
        });

        // Mock is_writable and is_readable to return true
        $this->setGlobalFunction('is_writable', static fn ($path) => true);
        $this->setGlobalFunction('is_readable', static fn ($path) => true);

        // Act
        $result = $this->pathGenerator->getStoreDirectory();

        // Assert
        $this->assertSame($storeDirectory, $result);
    }

    /**
     * Test getFileRelativePath method
     */
    public function testGetFileRelativePath(): void
    {
        // Arrange
        $fileRelativePath = 'relative/path';

        // Setup expectations for the mock
        $this->mockPathGeneratorInterface->expects($this->once())
            ->method('getFileRelativePath')
            ->willReturn($fileRelativePath);

        // Act
        $result = $this->pathGenerator->getFileRelativePath();

        // Assert
        $this->assertSame($fileRelativePath, $result);
    }

    /**
     * Test getPath method
     */
    public function testGetPath(): void
    {
        // Arrange
        $path = implode(DIRECTORY_SEPARATOR, ['path', 'to', 'file']) . DIRECTORY_SEPARATOR;

        // Setup expectations for the mock
        $this->mockPathGeneratorInterface->expects($this->once())
            ->method('getPath')
            ->willReturn($path);

        // Mock is_dir to return true for this specific path
        $this->setGlobalFunction('is_dir', static fn ($dirPath) => $dirPath === $path);

        // Act
        $result = $this->pathGenerator->getPath();

        // Assert
        $this->assertSame($path, $result);
    }

    /**
     * Test getPath method when path doesn't end with directory separator
     */
    public function testGetPathWhenPathDoesntEndWithDirectorySeparator(): void
    {
        // Arrange
        $path         = HOMEPATH . '/build/path/to/file';
        $expectedPath = $path . DIRECTORY_SEPARATOR;

        // Setup expectations for the mock
        $this->mockPathGeneratorInterface->expects($this->once())
            ->method('getPath')
            ->willReturn($path);

        // Mock is_dir to return true for the expected path (with directory separator)
        $this->setGlobalFunction('is_dir', static fn ($dirPath) => $dirPath === $expectedPath);

        // Act
        $result = $this->pathGenerator->getPath();

        // Assert
        $this->assertSame($expectedPath, $result);
    }

    /**
     * Test getStoreDirectoryForVariants method
     */
    public function testGetStoreDirectoryForVariants(): void
    {
        // Arrange
        $storeDirectory = HOMEPATH . '/build/path/to/store';

        // Setup expectations for the mock
        $this->mockPathGeneratorInterface->expects($this->once())
            ->method('getStoreDirectoryForVariants')
            ->willReturn($storeDirectory);

        // Mock is_dir to return true
        global $mockFunctions;
        $mockFunctions['is_dir'] = static fn ($path) => $path === $storeDirectory;

        // Act
        $result = $this->pathGenerator->getStoreDirectoryForVariants();

        // Assert
        $this->assertSame($storeDirectory, $result);
    }

    /**
     * Test getFileRelativePathForVariants method
     */
    public function testGetFileRelativePathForVariants(): void
    {
        // Arrange
        $fileRelativePath = 'relative/path/variants';

        // Setup expectations for the mock
        $this->mockPathGeneratorInterface->expects($this->once())
            ->method('getFileRelativePathForVariants')
            ->willReturn($fileRelativePath);

        // Act
        $result = $this->pathGenerator->getFileRelativePathForVariants();

        // Assert
        $this->assertSame($fileRelativePath, $result);
    }

    /**
     * Test getPathForVariants method
     */
    public function testGetPathForVariants(): void
    {
        // Arrange
        $path = implode(DIRECTORY_SEPARATOR, ['path', 'to', 'file', 'variants']) . DIRECTORY_SEPARATOR;

        // Setup expectations for the mock
        $this->mockPathGeneratorInterface->expects($this->once())
            ->method('getPathForVariants')
            ->willReturn($path);

        // Mock is_dir to return true
        global $mockFunctions;
        $mockFunctions['is_dir'] = static fn ($dirPath) => $dirPath === $path;

        // Act
        $result = $this->pathGenerator->getPathForVariants();

        // Assert
        $this->assertSame($path, $result);
    }
}
