<?php

declare(strict_types=1);

namespace Tests\Models;

use CodeIgniter\Config\Factories;
use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Test\CIUnitTestCase;
use Maniaba\AssetConnect\Config\Asset;
use Maniaba\AssetConnect\Config\Asset as AssetConfig;
use Maniaba\AssetConnect\Models\AssetModel;
use Override;
use RuntimeException;
use stdClass;

/**
 * @internal
 */
final class AssetModelTest extends CIUnitTestCase
{
    private ConnectionInterface $mockConnection;

    private Asset $mockAssetConfig;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockConnection  = $this->createMock(ConnectionInterface::class);
        $this->mockAssetConfig = $this->createMock(AssetConfig::class);

        // Setup global function mocks
        $this->setupGlobalFunctionMocks();
    }

    /**
     * Setup global function mocks
     */
    private function setupGlobalFunctionMocks(): void
    {
        // Inject mock for config
        Factories::injectMock('config', 'Asset', $this->mockAssetConfig);

        // Inject mock for AssetModel - used by testInitSuccessful
        $assetModel = new AssetModel($this->mockConnection);
        Factories::injectMock('models', AssetModel::class, $assetModel);
    }

    /**
     * Test successful initialization of AssetModel
     */
    public function testInitSuccessful(): void
    {
        // Arrange
        $this->mockAssetConfig->assetModel = AssetModel::class;

        // Act
        $result = AssetModel::init(true, $this->mockConnection);

        // Assert
        $this->assertInstanceOf(AssetModel::class, $result);
    }

    /**
     * Test initialization with invalid model class
     */
    public function testInitWithInvalidModelClass(): void
    {
        // Arrange
        $this->mockAssetConfig->assetModel = stdClass::class;

        // Act & Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Asset model class must extend ' . AssetModel::class);
        AssetModel::init(true, $this->mockConnection);
    }

    /**
     * Test initialization with invalid model instance
     */
    public function testInitWithInvalidModelInstance(): void
    {
        // Override the model mock to return an invalid instance
        $invalidInstance = new stdClass();
        Factories::injectMock('models', AssetModel::class, $invalidInstance);

        // Act & Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Asset model must be an instance of ' . AssetModel::class . ' or a subclass of it');
        AssetModel::init(true, $this->mockConnection);
    }

    /**
     * Test initialization with invalid return type
     */
    public function testInitWithInvalidReturnType(): void
    {
        // Create a valid AssetModel instance but with an invalid return type
        $invalidReturnTypeModel = model(AssetModel::class, false);
        $this->setPrivateProperty($invalidReturnTypeModel, 'returnType', stdClass::class);

        Factories::injectMock('models', AssetModel::class, $invalidReturnTypeModel);

        // Act & Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Asset model return type must be Asset or a subclass of Asset');
        AssetModel::init(true, $this->mockConnection);
    }
}
