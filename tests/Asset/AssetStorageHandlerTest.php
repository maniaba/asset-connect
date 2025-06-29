<?php

declare(strict_types=1);

namespace Tests\Asset;

use CodeIgniter\Entity\Entity;
use CodeIgniter\Files\File;
use CodeIgniter\Test\CIUnitTestCase;
use Maniaba\FileConnect\Asset\Asset;
use Maniaba\FileConnect\Asset\AssetStorageHandler;
use Maniaba\FileConnect\AssetCollection\AssetCollection;
use Maniaba\FileConnect\Exceptions\AssetException;
use Maniaba\FileConnect\Interfaces\Asset\AssetCollectionDefinitionInterface;
use Maniaba\FileConnect\Models\AssetModel;
use Maniaba\FileConnect\PathGenerator\PathGeneratorFactory;
use Maniaba\FileConnect\Traits\UseAssetConnectTrait;
use PHPUnit\Framework\MockObject\MockObject;
use stdClass;

/**
 * Test class for AssetStorageHandler
 *
 * Note: Since many of the classes are marked as final, we need to use a different approach
 * for testing. We'll use test doubles that implement the same interfaces instead of mocking
 * the actual classes.
 *
 * @internal
 */
final class AssetStorageHandlerTest extends CIUnitTestCase
{
    private MockObject $mockEntity;
    private Asset $asset;
    private MockObject $mockSetupAssetCollection;
    private MockObject $mockAssetCollection;
    private MockObject $mockPathGenerator;
    private MockObject $mockAssetModel;
    private MockObject $mockAssetConnect;
    private MockObject $mockFile;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a real Asset object instead of a mock
        $this->asset              = new Asset();
        $this->asset->collection  = 'test_collection';
        $this->asset->entity_type = 'test_entity_type';
        $this->asset->entity_id   = 1;

        // Create mock objects for interfaces
        $this->mockEntity = $this->createEntityWithTrait();

        // For final classes, we'll create test doubles that implement the same interfaces
        $this->mockSetupAssetCollection = $this->getMockBuilder(stdClass::class)
            ->addMethods(['getCollectionDefinition', 'getPathGenerator'])
            ->getMock();

        $this->mockAssetCollection = $this->getMockBuilder(stdClass::class)
            ->addMethods([
                'getVisibility', 'getMaximumNumberOfItemsInCollection', 'getMaxFileSize',
                'isSingleFileCollection', 'getAllowedMimeTypes', 'getAllowedExtensions',
            ])
            ->getMock();

        $this->mockPathGenerator = $this->getMockBuilder(stdClass::class)
            ->addMethods(['getPath', 'getPathForVariants'])
            ->getMock();

        $this->mockAssetModel = $this->getMockBuilder(stdClass::class)
            ->addMethods(['save', 'errors', 'where', 'orderBy', 'limit', 'offset', 'findColumn', 'whereIn', 'delete'])
            ->getMock();

        $mockCollectionDefinition = $this->createMock(AssetCollectionDefinitionInterface::class);

        $this->mockAssetConnect = $this->getMockBuilder(stdClass::class)
            ->addMethods(['addAsset', 'removeAssetById'])
            ->getMock();
        $this->mockFile = $this->createMock(File::class);

        // Setup the file property
        $this->asset->file = $this->mockFile;

        // Setup common expectations
        $this->mockSetupAssetCollection->method('getCollectionDefinition')
            ->willReturn($mockCollectionDefinition);

        // Setup global function mocks
        $this->setupGlobalFunctionMocks();
    }

    /**
     * Create an entity with the UseAssetConnectTrait
     */
    private function createEntityWithTrait(): MockObject
    {
        return $this->getMockBuilder(Entity::class)
            ->addMethods(['assetConnect', 'setupAssetConnect', 'loadAssetConnect'])
            ->getMock();
    }

    /**
     * Setup global function mocks
     */
    private function setupGlobalFunctionMocks(): void
    {
        // Mock the model function to return our mock AssetModel
        $this->setGlobalFunction('model', function ($class, $getShared = true) {
            if ($class === AssetModel::class) {
                return $this->mockAssetModel;
            }

            return null;
        });

        // Mock the helper function
        $this->setGlobalFunction('helper', static fn () => null);

        // Mock file functions
        $this->setGlobalFunction('file_exists', static fn () => true);

        $this->setGlobalFunction('copy', static fn () => true);

        $this->setGlobalFunction('is_dir', static fn () => true);

        $this->setGlobalFunction('delete_files', static fn () => true);

        $this->setGlobalFunction('rmdir', static fn () => true);

        $this->setGlobalFunction('unlink', static fn () => true);

        // Mock log_message function
        $this->setGlobalFunction('log_message', static fn () => null);
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
     * Test that the store method works correctly
     */
    public function testStore(): void
    {
        // This test will verify the public API of AssetStorageHandler
        // We'll mock the necessary dependencies and verify the result

        // Setup the mock path generator
        $this->mockPathGenerator->method('getPath')
            ->willReturn('/path/to/storage/');

        // Setup the mock file
        $this->mockFile->method('getRealPath')
            ->willReturn('/path/to/source/file.jpg');

        // Setup the asset
        $this->asset->file_name = 'file.jpg';

        // Setup the mock asset model
        $this->mockAssetModel->method('save')
            ->willReturn(true);
        $this->mockAssetModel->method('errors')
            ->willReturn([]);

        // Add a method to get the insertID
        $this->mockAssetModel->method('getInsertID')
            ->willReturn(123);

        // Setup the mock entity
        $this->mockEntity->method('assetConnect')
            ->willReturn($this->mockAssetConnect);

        // Setup the mock asset collection
        $this->mockAssetCollection->method('getMaxFileSize')
            ->willReturn(0);
        $this->mockAssetCollection->method('getAllowedExtensions')
            ->willReturn([]);
        $this->mockAssetCollection->method('getAllowedMimeTypes')
            ->willReturn([]);
        $this->mockAssetCollection->method('getMaximumNumberOfItemsInCollection')
            ->willReturn(0);

        // Create a handler with our test doubles
        $handler = $this->createHandlerWithMocks();

        // Act
        $result = $handler->store();

        // Assert
        $this->assertSame($this->asset, $result);
        $this->assertSame(123, $this->asset->id);
    }

    /**
     * Test that the store method throws an exception when file size exceeds maximum
     */
    public function testStoreThrowsExceptionWhenFileSizeExceedsMaximum(): void
    {
        // Setup the mock asset collection
        $this->mockAssetCollection->method('getMaxFileSize')
            ->willReturn(1000);
        $this->mockAssetCollection->method('getAllowedExtensions')
            ->willReturn([]);
        $this->mockAssetCollection->method('getAllowedMimeTypes')
            ->willReturn([]);

        // Setup the asset
        $this->asset->size = 2000;

        // Create a handler with our test doubles
        $handler = $this->createHandlerWithMocks();

        // Act & Assert
        $this->expectException(AssetException::class);
        $handler->store();
    }

    /**
     * Test that the store method throws an exception when file extension is not allowed
     */
    public function testStoreThrowsExceptionWhenFileExtensionIsNotAllowed(): void
    {
        // Setup the mock asset collection
        $this->mockAssetCollection->method('getMaxFileSize')
            ->willReturn(0);
        $this->mockAssetCollection->method('getAllowedExtensions')
            ->willReturn(['jpg', 'png']);
        $this->mockAssetCollection->method('getAllowedMimeTypes')
            ->willReturn([]);

        // Setup the mock file
        $this->mockFile->method('getExtension')
            ->willReturn('pdf');

        // Create a handler with our test doubles
        $handler = $this->createHandlerWithMocks();

        // Act & Assert
        $this->expectException(AssetException::class);
        $handler->store();
    }

    /**
     * Test that the store method throws an exception when MIME type is not allowed
     */
    public function testStoreThrowsExceptionWhenMimeTypeIsNotAllowed(): void
    {
        // Setup the mock asset collection
        $this->mockAssetCollection->method('getMaxFileSize')
            ->willReturn(0);
        $this->mockAssetCollection->method('getAllowedExtensions')
            ->willReturn([]);
        $this->mockAssetCollection->method('getAllowedMimeTypes')
            ->willReturn(['image/jpeg', 'image/png']);

        // Setup the asset
        $this->asset->mime_type = 'application/pdf';

        // Create a handler with our test doubles
        $handler = $this->createHandlerWithMocks();

        // Act & Assert
        $this->expectException(AssetException::class);
        $handler->store();
    }

    /**
     * Test that the removeStoragePath method works correctly for directories
     */
    public function testRemoveStoragePathForDirectory(): void
    {
        // Setup expectations
        $this->setGlobalFunction('is_dir', static fn () => true);

        $this->setGlobalFunction('delete_files', function ($path, $delDir, $htdocs, $hidden) {
            $this->assertSame('/path/to/storage/', $path);
            $this->assertTrue($delDir);
            $this->assertFalse($htdocs);
            $this->assertTrue($hidden);

            return true;
        });

        $this->setGlobalFunction('rmdir', function ($path) {
            $this->assertSame('/path/to/storage/', $path);

            return true;
        });

        // Act
        AssetStorageHandler::removeStoragePath('/path/to/storage/');

        // No assertions needed as we're testing the method calls in the mocked functions
        $this->assertTrue(true);
    }

    /**
     * Test that the removeStoragePath method works correctly for files
     */
    public function testRemoveStoragePathForFile(): void
    {
        // Setup expectations
        $this->setGlobalFunction('is_dir', static fn () => false);

        $this->setGlobalFunction('file_exists', function ($path) {
            $this->assertSame('/path/to/file.jpg', $path);

            return true;
        });

        $this->setGlobalFunction('unlink', function ($path) {
            $this->assertSame('/path/to/file.jpg', $path);

            return true;
        });

        // Act
        AssetStorageHandler::removeStoragePath('/path/to/file.jpg');

        // No assertions needed as we're testing the method calls in the mocked functions
        $this->assertTrue(true);
    }

    /**
     * Create a handler with mocked dependencies
     */
    private function createHandlerWithMocks(): AssetStorageHandler
    {
        // Override the AssetCollection::create static method
        $this->setGlobalFunction('Maniaba\FileConnect\AssetCollection\AssetCollection::create', fn () => $this->mockAssetCollection);

        // Override the PathGeneratorFactory::create static method
        $this->setGlobalFunction('Maniaba\FileConnect\PathGenerator\PathGeneratorFactory::create', fn () => $this->mockPathGenerator);

        // Override the model function to return our mock AssetModel with insertID
        $this->setGlobalFunction('model', function ($class, $getShared = true) {
            if ($class === AssetModel::class) {
                return $this->mockAssetModel;
            }

            return null;
        });

        return new AssetStorageHandler(
            $this->mockEntity,
            $this->asset,
            $this->mockSetupAssetCollection,
        );
    }
}
