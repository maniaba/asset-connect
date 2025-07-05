<?php

declare(strict_types=1);

namespace Tests\AssetVariants;

use CodeIgniter\Test\CIUnitTestCase;
use Maniaba\AssetConnect\Asset\Asset;
use Maniaba\AssetConnect\AssetVariants\Interfaces\CreateAssetVariantsInterface;
use Maniaba\AssetConnect\AssetVariants\AssetVariant;
use Maniaba\AssetConnect\AssetVariants\AssetVariants;
use Maniaba\AssetConnect\PathGenerator\PathGenerator;
use Override;

/**
 * @internal
 */
final class AssetVariantsTest extends CIUnitTestCase
{
    private Asset $asset;
    private CreateAssetVariantsInterface $assetVariants;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        // Create Asset instance
        $this->asset = new Asset();

        // Set up the asset with required properties
        $this->asset->file_name = 'test_image.jpg';

        // Create TestAssetVariants instance
        $this->assetVariants = new AssetVariants(
            $this->mockPathGenerator(),
            $this->asset,
        );
    }

    private function mockPathGenerator(): PathGenerator
    {
        // Create a mock PathGenerator instance
        $pathGenerator = $this->createMock(PathGenerator::class);

        // Set expectations for the methods used in the tests
        $pathGenerator->method('getPathForVariants')
            ->willReturn(HOMEPATH . '/build/path/to/variants/');
        $pathGenerator->method('getStoreDirectoryForVariants')
            ->willReturn(HOMEPATH . '/build/path/to/');
        $pathGenerator->method('getFileRelativePathForVariants')
            ->willReturn('variants/');

        return $pathGenerator;
    }

    /**
     * Test assetVariant method creates and returns a new AssetVariant
     */
    public function testAssetVariantCreatesAndReturnsNewAssetVariant(): void
    {
        // Arrange
        $variantName = 'thumbnail';
        $variantPath = HOMEPATH . '/build/path/to/variants/';

        // Define a simple closure for the variant
        $closure = static function (AssetVariant $variant, Asset $asset) {
            // This closure is just a placeholder and won't be executed in this test
        };

        // Act
        $result = $this->assetVariants->assetVariant($variantName, $closure);

        // Assert
        $this->assertInstanceOf(AssetVariant::class, $result);
        $this->assertSame($variantName, $result->name);
        $this->assertSame($variantPath . 'test_image-' . $variantName . '.jpg', $result->path);
        $this->assertSame(0, $result->size);
        $this->assertFalse($result->processed);

        // Verify the variant was added to the asset's metadata
        $variants = $this->asset->metadata->assetVariant->getVariants();
        $this->assertArrayHasKey($variantName, $variants);
        $this->assertSame($result, $variants[$variantName]);
    }

    /**
     * Test assetVariant method with different file name formats
     */
    public function testAssetVariantWithDifferentFileNameFormats(): void
    {
        // Arrange
        $variantName = 'medium';
        $variantPath = HOMEPATH . '/build/path/to/variants/';

        // Test with different file names
        $fileNames = [
            'image.jpg'            => 'image-medium.jpg',
            'image.with.dots.jpg'  => 'image.with.dots-medium.jpg',
            'image_underscore.jpg' => 'image_underscore-medium.jpg',
            'image-dash.jpg'       => 'image-dash-medium.jpg',
            'image'                => 'image-medium.',  // No extension
        ];

        foreach ($fileNames as $fileName => $expectedVariantName) {
            // Set the file name
            $this->asset->file_name = $fileName;

            // Define a simple closure for the variant
            $closure = static function (AssetVariant $variant, Asset $asset) {
                // This closure is just a placeholder and won't be executed in this test
            };

            // Act
            $result = $this->assetVariants->assetVariant($variantName, $closure);

            // Assert
            $this->assertInstanceOf(AssetVariant::class, $result);
            $this->assertSame($variantPath . $expectedVariantName, $result->path);
        }
    }

    /**
     * Test onQueue property is false by default
     */
    public function testOnQueuePropertyIsFalseByDefault(): void
    {
        // Assert
        $this->assertFalse($this->assetVariants->onQueue);
    }
}
