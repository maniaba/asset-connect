<?php

declare(strict_types=1);

namespace Tests\AssetVariants;

use Closure;
use CodeIgniter\Config\Factories;
use CodeIgniter\Test\CIUnitTestCase;
use Maniaba\AssetConnect\Asset\Asset;
use Maniaba\AssetConnect\Asset\AssetMetadata;
use Maniaba\AssetConnect\AssetVariants\AssetVariant;
use Maniaba\AssetConnect\AssetVariants\AssetVariantsProcessor;
use Maniaba\AssetConnect\Config\Asset as AssetConfig;
use Maniaba\AssetConnect\Enums\AssetVisibility;
use Maniaba\AssetConnect\Exceptions\FileVariantException;
use Maniaba\AssetConnect\Storage\Interfaces\StorageDiskInterface;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use Tests\Support\Config\TestAssetConfig;

/**
 * @internal
 */
final class AssetVariantsProcessorTest extends CIUnitTestCase
{
    private Asset $asset;
    private AssetVariantsProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();
        // Create Asset instance
        $this->asset     = new Asset();
        $this->asset->id = 123;
        // Create AssetMetadata instance and set it to the asset
        $metadata    = new AssetMetadata();
        $setMetadata = $this->getPrivateMethodInvoker($this->asset, 'setMetadata');
        $setMetadata($metadata);
        // Create a variant and add it to the asset's metadata
        $variant = new AssetVariant([
            'name'      => 'thumbnail',
            'storage'   => 'variant',
            'path'      => '/path/to/variants/image-thumbnail.jpg',
            'size'      => 0,
            'processed' => false,
        ]);
        $this->asset->metadata->assetVariant->addAssetVariant($variant);
        // Create AssetVariantsProcessor instance
        $this->processor = new AssetVariantsProcessor($this->asset);
        // Setup global function mocks
        $this->setupGlobalFunctionMocks();
    }

    /**
     * Setup global function mocks
     */
    private function setupGlobalFunctionMocks(): void
    {
        global $mockFunctions;

        // Mock log_message function
        $mockFunctions['log_message'] = static fn () => null;

        // Mock file_exists function
        $mockFunctions['file_exists'] = static fn () => true;

        // Mock filesize function
        $mockFunctions['filesize'] = static fn () => 1024;
    }

    private function configureVariantStorageDisk(bool $fileExists, int $fileSize = 2048): MockObject&StorageDiskInterface
    {
        $disk = $this->createMock(StorageDiskInterface::class);
        $disk->method('name')->willReturn('variant');
        $disk->method('visibility')->willReturn(AssetVisibility::PUBLIC);
        $disk->expects($this->once())
            ->method('fileExists')
            ->with('/path/to/variants/image-thumbnail.jpg')
            ->willReturn($fileExists);

        if ($fileExists) {
            $disk->expects($this->once())
                ->method('fileSize')
                ->with('/path/to/variants/image-thumbnail.jpg')
                ->willReturn($fileSize);
        } else {
            $disk->expects($this->never())->method('fileSize');
        }

        $config                      = new TestAssetConfig();
        $config->storages['variant'] = [
            'disk' => $disk,
        ];

        Factories::injectMock('config', AssetConfig::class, $config);
        Factories::injectMock('config', 'Asset', $config);

        return $disk;
    }

    /**
     * Test assetVariant method returns null when variant is not found
     */
    public function testAssetVariantReturnsNullWhenVariantNotFound(): void
    {
        // Arrange
        $variantName = 'nonexistent';

        // Define a closure that should not be called
        $closure = function (AssetVariant $variant, Asset $asset) {
            $this->fail('Closure should not be called when variant is not found');
        };

        // Act
        $result = $this->processor->assetVariant($variantName, $closure);

        // Assert
        $this->assertNotInstanceOf(AssetVariant::class, $result);
    }

    /**
     * Test assetVariant method throws exception when closure throws exception
     */
    public function testAssetVariantThrowsExceptionWhenClosureThrowsException(): void
    {
        // Arrange
        $variantName = 'thumbnail';

        // Define a closure that throws an exception
        $closure = static function (AssetVariant $variant, Asset $asset) {
            throw new RuntimeException('Test exception');
        };

        // Act & Assert
        $this->expectException(FileVariantException::class);
        $this->expectExceptionMessage('Test exception');
        $this->processor->assetVariant($variantName, $closure);
    }

    public function testAssetVariantProcessesExistingVariantAndUpdatesMetadata(): void
    {
        $this->configureVariantStorageDisk(true, 4096);

        $closureWasCalled = false;

        $result = $this->processor->assetVariant('thumbnail', function (AssetVariant $variant, Asset $asset) use (&$closureWasCalled): void {
            $closureWasCalled = true;

            $this->assertSame('thumbnail', $variant->name);
            $this->assertSame($this->asset, $asset);
        });

        $this->assertTrue($closureWasCalled);
        $this->assertInstanceOf(AssetVariant::class, $result);
        $this->assertSame(4096, $result->size);
        $this->assertTrue($result->processed);

        $updatedVariant = $this->asset->metadata->assetVariant->getAssetVariant('thumbnail');

        $this->assertInstanceOf(AssetVariant::class, $updatedVariant);
        $this->assertSame(4096, $updatedVariant->size);
        $this->assertTrue($updatedVariant->processed);
    }

    public function testAssetVariantThrowsWhenProcessedFileDoesNotExist(): void
    {
        $this->configureVariantStorageDisk(false);

        $this->expectException(FileVariantException::class);

        $this->processor->assetVariant('thumbnail', static function (): void {
        });
    }

    /**
     * Test onQueue property is true by default
     */
    public function testOnQueuePropertyIsTrueByDefault(): void
    {
        // Assert
        $this->assertTrue($this->processor->onQueue);
    }
}
