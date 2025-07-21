<?php

declare(strict_types=1);

namespace Tests\AssetVariants;

use CodeIgniter\Test\CIUnitTestCase;
use Maniaba\AssetConnect\Asset\Asset;
use Maniaba\AssetConnect\AssetVariants\AssetVariant;
use Maniaba\AssetConnect\AssetVariants\AssetVariants;
use Maniaba\AssetConnect\AssetVariants\Interfaces\CreateAssetVariantsInterface;
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

    /**
     * Test assetVariant method with custom extension
     */
    public function testAssetVariantWithCustomExtension(): void
    {
        // Arrange
        $variantName     = 'webp_variant';
        $customExtension = 'webp';
        $variantPath     = HOMEPATH . '/build/path/to/variants/';

        // Define a simple closure for the variant
        $closure = static function (AssetVariant $variant, Asset $asset) {
            // This closure is just a placeholder and won't be executed in this test
        };

        // Act
        $result = $this->assetVariants->assetVariant($variantName, $closure, $customExtension);

        // Assert
        $this->assertInstanceOf(AssetVariant::class, $result);
        $this->assertSame($variantName, $result->name);
        $this->assertSame($variantPath . 'test_image-' . $variantName . '.webp', $result->path);
        $this->assertSame(0, $result->size);
        $this->assertFalse($result->processed);

        // Verify the variant was added to the asset's metadata
        $variants = $this->asset->metadata->assetVariant->getVariants();
        $this->assertArrayHasKey($variantName, $variants);
        $this->assertSame($result, $variants[$variantName]);
    }

    /**
     * Test assetVariant method uses original extension when extension parameter is null
     */
    public function testAssetVariantWithNullExtensionUsesOriginal(): void
    {
        // Arrange
        $variantName = 'null_extension_test';
        $variantPath = HOMEPATH . '/build/path/to/variants/';

        // Define a simple closure for the variant
        $closure = static function (AssetVariant $variant, Asset $asset) {
            // This closure is just a placeholder and won't be executed in this test
        };

        // Act
        $result = $this->assetVariants->assetVariant($variantName, $closure, null);

        // Assert
        $this->assertInstanceOf(AssetVariant::class, $result);
        $this->assertSame($variantName, $result->name);
        $this->assertSame($variantPath . 'test_image-' . $variantName . '.jpg', $result->path);
    }

    /**
     * Test assetVariant method with various custom extensions
     */
    public function testAssetVariantWithVariousCustomExtensions(): void
    {
        $variantPath = HOMEPATH . '/build/path/to/variants/';
        $closure     = static function (AssetVariant $variant, Asset $asset) {};

        $testCases = [
            ['png', 'test_image-variant.png'],
            ['webp', 'test_image-variant.webp'],
            ['avif', 'test_image-variant.avif'],
            ['gif', 'test_image-variant.gif'],
            ['bmp', 'test_image-variant.bmp'],
            ['svg', 'test_image-variant.svg'],
        ];

        foreach ($testCases as [$extension, $expectedFileName]) {
            // Act
            $result = $this->assetVariants->assetVariant('variant', $closure, $extension);

            // Assert
            $this->assertSame($variantPath . $expectedFileName, $result->path, "Failed for extension: {$extension}");
        }
    }

    /**
     * Test assetVariant method with different original file extensions
     */
    public function testAssetVariantWithDifferentOriginalExtensions(): void
    {
        $variantPath = HOMEPATH . '/build/path/to/variants/';
        $closure     = static function (AssetVariant $variant, Asset $asset) {};

        $testCases = [
            ['document.pdf', 'pdf', 'document-variant.pdf'],
            ['video.mp4', 'mp4', 'video-variant.mp4'],
            ['audio.mp3', 'mp3', 'audio-variant.mp3'],
            ['archive.zip', 'zip', 'archive-variant.zip'],
            ['text.txt', 'txt', 'text-variant.txt'],
        ];

        foreach ($testCases as [$fileName, $expectedExt, $expectedFileName]) {
            // Arrange - change the asset file name for each test
            $this->asset->file_name = $fileName;

            // Act - without custom extension (should use original)
            $result = $this->assetVariants->assetVariant('variant', $closure);

            // Assert
            $this->assertSame($variantPath . $expectedFileName, $result->path, "Failed for file: {$fileName}");
        }
    }

    /**
     * Test assetVariant method custom extension overrides original extension
     */
    public function testAssetVariantCustomExtensionOverridesOriginal(): void
    {
        // Arrange
        $this->asset->file_name = 'image.jpg'; // Original is JPG
        $variantName            = 'converted';
        $customExtension        = 'png'; // Convert to PNG
        $variantPath            = HOMEPATH . '/build/path/to/variants/';

        $closure = static function (AssetVariant $variant, Asset $asset) {};

        // Act
        $result = $this->assetVariants->assetVariant($variantName, $closure, $customExtension);

        // Assert
        $this->assertSame($variantPath . 'image-' . $variantName . '.png', $result->path);
        $this->assertStringEndsWith('.png', $result->path);
        $this->assertStringNotContainsString('.jpg', $result->path);
    }
}
