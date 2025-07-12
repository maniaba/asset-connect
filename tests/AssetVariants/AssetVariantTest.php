<?php

declare(strict_types=1);

namespace Tests\AssetVariants;

use CodeIgniter\Test\CIUnitTestCase;
use Maniaba\AssetConnect\AssetVariants\AssetVariant;
use Maniaba\AssetConnect\Exceptions\FileVariantException;
use Override;
use ReflectionClass;

/**
 * @internal
 */
final class AssetVariantTest extends CIUnitTestCase
{
    private AssetVariant $assetVariant;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->assetVariant = new AssetVariant();

        // Setup global function mocks
        $this->setupGlobalFunctionMocks();
    }

    /**
     * Setup global function mocks
     */
    private function setupGlobalFunctionMocks(): void
    {
        global $mockFunctions;

        // Mock helper function
        $mockFunctions['helper'] = static fn () => null;

        // Mock write_file function
        $mockFunctions['write_file'] = static fn () => true;

        // Mock file_exists function
        $mockFunctions['file_exists'] = static fn () => true;

        // Mock filesize function
        $mockFunctions['filesize'] = static fn () => 1024;
    }

    /**
     * Test writeFile method successfully writes a file
     */
    public function testWriteFileSuccessfully(): void
    {
        // Arrange
        $path = HOMEPATH . 'build/path/to/write_method/file.txt';

        // Ensure the directory exists
        $dir = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $this->assetVariant->path = $path;
        $data                     = 'file content';

        // Act
        $result = $this->assetVariant->writeFile($data);

        // Assert
        $this->assertTrue($result);
        $this->assertSame(12, $this->assetVariant->size);
        $this->assertTrue($this->assetVariant->processed);
    }

    /**
     * Test getRelativePath method returns correct path
     */
    public function testGetRelativePathReturnsCorrectPath(): void
    {
        // Arrange
        $this->assetVariant->path  = '/storage/path/to/file.jpg';
        $this->assetVariant->paths = [
            'storage_base_directory_path' => '/storage',
            'file_relative_path'          => 'path/to',
        ];

        $method = $this->getPrivateMethodInvoker($this->assetVariant, 'getRelativePath');

        // Act
        $result = $method($this->assetVariant);

        // Assert
        $this->assertSame('/path/to/file.jpg', $result);
    }

    /**
     * Test getRelativePath method throws exception when storage base path is not set
     */
    public function testGetRelativePathThrowsExceptionWhenStorageBasePathIsNotSet(): void
    {
        // Arrange
        $this->assetVariant->path  = '/path/to/file.jpg';
        $this->assetVariant->paths = (object) [
            'file_relative_path' => 'path/to',
        ];

        // Use reflection to access protected method
        $reflection = new ReflectionClass($this->assetVariant);
        $method     = $reflection->getMethod('getRelativePath');
        $method->setAccessible(true);

        // Act & Assert
        $this->expectException(FileVariantException::class);
        $method->invoke($this->assetVariant);
    }

    /**
     * Test getRelativePathForUrl method returns correct URL path
     */
    public function testGetRelativePathForUrlReturnsCorrectUrlPath(): void
    {
        // Arrange
        $this->assetVariant->path  = '/storage/path/to/file.jpg';
        $this->assetVariant->paths = [
            'storage_base_directory_path' => '/storage',
            'file_relative_path'          => 'path/to',
        ];

        // Use reflection to access protected method
        $reflection = new ReflectionClass($this->assetVariant);
        $method     = $reflection->getMethod('getRelativePathForUrl');
        $method->setAccessible(true);

        // Act
        $result = $method->invoke($this->assetVariant);

        // Assert
        $this->assertSame('/path/to/file.jpg', $result);
    }

    /**
     * Test getRelativePathForUrl method replaces backslashes with forward slashes
     */
    public function testGetRelativePathForUrlReplacesBackslashesWithForwardSlashes(): void
    {
        // Arrange
        $this->assetVariant->path  = '/storage/path\\to\\file.jpg';
        $this->assetVariant->paths = [
            'storage_base_directory_path' => '/storage',
            'file_relative_path'          => 'path\\to',
        ];

        // First get the relative path
        $getRelativePathMethod = $this->getPrivateMethodInvoker($this->assetVariant, 'getRelativePath');
        $relativePath = $getRelativePathMethod();

        // Verify the relative path contains backslashes
        $this->assertStringContainsString('\\', $relativePath);

        // Now test the getRelativePathForUrl method
        $getRelativePathForUrlMethod = $this->getPrivateMethodInvoker($this->assetVariant,'getRelativePathForUrl');

        // Act
        $result = $getRelativePathForUrlMethod();

        // Assert
        $this->assertStringNotContainsString('\\', $result);
        $this->assertStringContainsString('/', $result);
    }
}
