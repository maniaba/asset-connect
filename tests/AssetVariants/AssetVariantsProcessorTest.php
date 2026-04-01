<?php

declare(strict_types=1);

namespace Tests\AssetVariants;

use Closure;
use CodeIgniter\Test\CIUnitTestCase;
use Maniaba\AssetConnect\Asset\Asset;
use Maniaba\AssetConnect\Asset\AssetMetadata;
use Maniaba\AssetConnect\AssetVariants\AssetVariant;
use Maniaba\AssetConnect\AssetVariants\AssetVariantsProcessor;
use Maniaba\AssetConnect\Exceptions\FileVariantException;
use Override;
use RuntimeException;

/**
 * @internal
 */
final class AssetVariantsProcessorTest extends CIUnitTestCase
{
    private AssetVariantsProcessor $processor;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        // Create Asset instance
        $asset     = new Asset();
        $asset->id = 123;

        // Create AssetMetadata instance and set it to the asset
        $metadata    = new AssetMetadata();
        $setMetadata = $this->getPrivateMethodInvoker($asset, 'setMetadata');
        $setMetadata($metadata);

        // Create a variant and add it to the asset's metadata
        $variant = new AssetVariant([
            'name'      => 'thumbnail',
            'path'      => '/path/to/variants/image-thumbnail.jpg',
            'size'      => 0,
            'processed' => false,
        ]);

        $asset->metadata->assetVariant->addAssetVariant($variant);

        // Create AssetVariantsProcessor instance
        $this->processor = new AssetVariantsProcessor($asset);

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

    /**
     * Test onQueue property is true by default
     */
    public function testOnQueuePropertyIsTrueByDefault(): void
    {
        // Assert
        $this->assertTrue($this->processor->onQueue);
    }
}
