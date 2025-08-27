<?php

declare(strict_types=1);

namespace Tests\AssetCollection;

use CodeIgniter\Test\CIUnitTestCase;
use Maniaba\AssetConnect\Asset\Interfaces\AssetCollectionDefinitionInterface;
use Maniaba\AssetConnect\Asset\Interfaces\AuthorizableAssetCollectionDefinitionInterface;
use Maniaba\AssetConnect\AssetCollection\AssetCollection;
use Maniaba\AssetConnect\AssetCollection\SetupAssetCollection;
use Maniaba\AssetConnect\Enums\AssetExtension;
use Maniaba\AssetConnect\Enums\AssetMimeType;
use Maniaba\AssetConnect\Enums\AssetVisibility;
use Maniaba\AssetConnect\Exceptions\InvalidArgumentException;
use Maniaba\AssetConnect\PathGenerator\Interfaces\PathGeneratorInterface;
use Override;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @internal
 */
final class AssetCollectionTest extends CIUnitTestCase
{
    private SetupAssetCollection $setupAssetCollection;

    /**
     * @var AssetCollectionDefinitionInterface&MockObject
     */
    private MockObject $mockCollectionDefinition;

    /**
     * @var MockObject&PathGeneratorInterface
     */
    private MockObject $mockPathGenerator;

    private AssetCollection $assetCollection;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        // Create real instance of SetupAssetCollection
        $this->setupAssetCollection     = new SetupAssetCollection();
        $this->mockCollectionDefinition = $this->createMock(AssetCollectionDefinitionInterface::class);
        $this->mockPathGenerator        = $this->createMock(PathGeneratorInterface::class);

        $this->setPrivateProperty($this->setupAssetCollection, 'collectionDefinition', $this->mockCollectionDefinition);

        // Setup global function mocks
        $this->setupGlobalFunctionMocks();

        // Create the AssetCollection instance
        $this->assetCollection = AssetCollection::create($this->setupAssetCollection);
    }

    /**
     * Setup global function mocks
     */
    private function setupGlobalFunctionMocks(): void
    {
        global $mockFunctions;

        // Mock PhpIni::uploadMaxFilesizeBytes
        $mockFunctions['Maniaba\AssetConnect\Utils\PhpIni::uploadMaxFilesizeBytes'] = static fn () => 2097152; // 2MB
    }

    /**
     * Test create method creates a new AssetCollection instance
     */
    public function testCreateReturnsAssetCollectionInstance(): void
    {
        // Act
        $result = AssetCollection::create($this->setupAssetCollection);

        // Assert
        $this->assertInstanceOf(AssetCollection::class, $result);
    }

    /**
     * Test constructor calls definition method on collection definition
     */
    public function testConstructorCallsDefinitionMethod(): void
    {
        // Arrange
        $this->mockCollectionDefinition->expects($this->once())
            ->method('definition')
            ->with($this->isInstanceOf(AssetCollection::class));

        // Act
        AssetCollection::create($this->setupAssetCollection);

        // Assert is handled by the expects() assertion
    }

    /**
     * Test constructor sets visibility to protected if collection implements AuthorizableAssetCollectionDefinitionInterface
     */
    public function testConstructorSetsVisibilityToProtectedForAuthorizableCollection(): void
    {
        // Arrange
        $mockAuthorizableDefinition = $this->createMock(AuthorizableAssetCollectionDefinitionInterface::class);

        // Setup SetupAssetCollection to use our mock authorizable collection definition
        $this->setPrivateProperty($this->setupAssetCollection, 'collectionDefinition', $mockAuthorizableDefinition);

        // Act
        $collection = AssetCollection::create($this->setupAssetCollection);

        // Assert
        $this->assertSame(AssetVisibility::PROTECTED, $collection->getVisibility());
    }

    /**
     * Test allowedExtensions method with valid extensions
     */
    public function testAllowedExtensionsWithValidExtensions(): void
    {
        // Arrange
        $extensions = ['jpg', 'png', 'pdf'];

        // Act
        $result = $this->assetCollection->allowedExtensions(...$extensions);

        // Assert
        $this->assertSame($this->assetCollection, $result);
        $this->assertSame($extensions, $this->assetCollection->getAllowedExtensions());
    }

    /**
     * Test allowedExtensions method with AssetExtension enum
     */
    public function testAllowedExtensionsWithAssetExtensionEnum(): void
    {
        // Arrange
        $extensions         = [AssetExtension::JPG, AssetExtension::PNG, AssetExtension::PDF];
        $expectedExtensions = ['jpg', 'png', 'pdf'];

        // Act
        $result = $this->assetCollection->allowedExtensions(...$extensions);

        // Assert
        $this->assertSame($this->assetCollection, $result);
        $this->assertSame($expectedExtensions, $this->assetCollection->getAllowedExtensions());
    }

    /**
     * Test allowedExtensions method with invalid extension
     */
    public function testAllowedExtensionsWithInvalidExtension(): void
    {
        // Arrange
        $invalidExtension = 'invalid!extension';

        // Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->assetCollection->allowedExtensions($invalidExtension);
    }

    /**
     * Test allowedExtensions method with empty extension
     */
    public function testAllowedExtensionsWithEmptyExtension(): void
    {
        // Arrange
        $emptyExtension = '';

        // Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->assetCollection->allowedExtensions($emptyExtension);
    }

    /**
     * Test allowedExtensions method with extension starting with dot
     */
    public function testAllowedExtensionsWithExtensionStartingWithDot(): void
    {
        // Arrange
        $extensionWithDot = '.jpg';

        // Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->assetCollection->allowedExtensions($extensionWithDot);
    }

    /**
     * Test allowedMimeTypes method with valid MIME types
     */
    public function testAllowedMimeTypesWithValidMimeTypes(): void
    {
        // Arrange
        $mimeTypes = ['image/jpeg', 'image/png', 'application/pdf'];

        // Act
        $result = $this->assetCollection->allowedMimeTypes(...$mimeTypes);

        // Assert
        $this->assertSame($this->assetCollection, $result);
        $this->assertSame($mimeTypes, $this->assetCollection->getAllowedMimeTypes());
    }

    /**
     * Test allowedMimeTypes method with AssetMimeType enum
     */
    public function testAllowedMimeTypesWithAssetMimeTypeEnum(): void
    {
        // Arrange
        $mimeTypes         = [AssetMimeType::IMAGE_JPEG, AssetMimeType::IMAGE_PNG, AssetMimeType::APPLICATION_PDF];
        $expectedMimeTypes = ['image/jpeg', 'image/png', 'application/pdf'];

        // Act
        $result = $this->assetCollection->allowedMimeTypes(...$mimeTypes);

        // Assert
        $this->assertSame($this->assetCollection, $result);
        $this->assertSame($expectedMimeTypes, $this->assetCollection->getAllowedMimeTypes());
    }

    /**
     * Test allowedMimeTypes method with invalid MIME type
     */
    public function testAllowedMimeTypesWithInvalidMimeType(): void
    {
        // Arrange
        $invalidMimeType = 'invalid-mime-type';

        // Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->assetCollection->allowedMimeTypes($invalidMimeType);
    }

    /**
     * Test allowedMimeTypes method with empty MIME type
     */
    public function testAllowedMimeTypesWithEmptyMimeType(): void
    {
        // Arrange
        $emptyMimeType = '';

        // Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->assetCollection->allowedMimeTypes($emptyMimeType);
    }

    /**
     * Test setMaxFileSize method with valid size
     */
    public function testSetMaxFileSizeWithValidSize(): void
    {
        // Arrange
        $maxFileSize = 1048576; // 1MB

        // Act
        $result = $this->assetCollection->setMaxFileSize($maxFileSize);

        // Assert
        $this->assertSame($this->assetCollection, $result);
        $this->assertSame($maxFileSize, $this->assetCollection->getMaxFileSize());
    }

    /**
     * Test setMaxFileSize method with negative size
     */
    public function testSetMaxFileSizeWithNegativeSize(): void
    {
        // Arrange
        $negativeSize = -1;

        // Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->assetCollection->setMaxFileSize($negativeSize);
    }

    /**
     * Test onlyKeepLatest method with valid number
     */
    public function testOnlyKeepLatestWithValidNumber(): void
    {
        // Arrange
        $maxItems = 5;

        // Act
        $result = $this->assetCollection->onlyKeepLatest($maxItems);

        // Assert
        $this->assertSame($this->assetCollection, $result);
        $this->assertSame($maxItems, $this->assetCollection->getMaximumNumberOfItemsInCollection());
    }

    /**
     * Test onlyKeepLatest method with negative number
     */
    public function testOnlyKeepLatestWithNegativeNumber(): void
    {
        // Arrange
        $negativeNumber = -1;

        // Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->assetCollection->onlyKeepLatest($negativeNumber);
    }

    /**
     * Test singleFileCollection method
     */
    public function testSingleFileCollection(): void
    {
        // Act
        $result = $this->assetCollection->singleFileCollection();

        // Assert
        $this->assertSame($this->assetCollection, $result);
        $this->assertSame(1, $this->assetCollection->getMaximumNumberOfItemsInCollection());
        $this->assertTrue($this->assetCollection->isSingleFileCollection());
    }

    /**
     * Test setVisibility method
     */
    public function testSetVisibility(): void
    {
        // Arrange
        $visibility = AssetVisibility::PROTECTED;

        // Act
        $result = $this->assetCollection->setVisibility($visibility);

        // Assert
        $this->assertSame($this->assetCollection, $result);
        $this->assertSame($visibility, $this->assetCollection->getVisibility());
        $this->assertTrue($this->assetCollection->isProtected());
    }

    /**
     * Test setPathGenerator and getPathGenerator methods
     */
    public function testSetAndGetPathGenerator(): void
    {
        // Act
        $result = $this->assetCollection->setPathGenerator($this->mockPathGenerator);

        // Assert
        $this->assertSame($this->assetCollection, $result);
        $this->assertSame($this->mockPathGenerator, $this->assetCollection->getPathGenerator());
    }

    /**
     * Test getPathGenerator method when path generator is not set
     */
    public function testGetPathGeneratorWhenNotSet(): void
    {
        // Arrange
        // Setup SetupAssetCollection to use our mock path generator
        $this->setPrivateProperty($this->setupAssetCollection, 'pathGenerator', $this->mockPathGenerator);

        // Act
        $result = $this->assetCollection->getPathGenerator();

        // Assert
        $this->assertSame($this->mockPathGenerator, $result);
    }
}
