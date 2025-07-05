<?php

declare(strict_types=1);

namespace Tests\Validation;

use CodeIgniter\Files\File;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;
use Maniaba\AssetConnect\Enums\AssetExtension;
use Maniaba\AssetConnect\Enums\AssetMimeType;
use Maniaba\AssetConnect\PathGenerator\Interfaces\PathGeneratorInterface;
use Maniaba\AssetConnect\Validation\ValidationRuleCollector;
use Override;

/**
 * @internal
 */
final class ValidationRuleCollectorTest extends CIUnitTestCase
{
    private ValidationRuleCollector $collector;
    private string $fieldName = 'testField';

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->collector = new ValidationRuleCollector($this->fieldName);
    }

    /**
     * Test constructor sets the uploaded rule
     */
    public function testConstructorSetsUploadedRule(): void
    {
        // Assert
        $rules = $this->collector->getRules();
        $this->assertContains('uploaded[' . $this->fieldName . ']', $rules);
    }

    /**
     * Test allowedExtensions method with enum values
     */
    public function testAllowedExtensionsWithEnumValues(): void
    {
        // Arrange
        $extensions = [AssetExtension::JPG, AssetExtension::PNG];

        // Act
        $result = $this->collector->allowedExtensions(...$extensions);
        $rules  = $this->collector->getRules();

        // Assert
        $this->assertSame($this->collector, $result);
        $this->assertContains('ext_in[' . $this->fieldName . ',jpg,png]', $rules);
    }

    /**
     * Test allowedExtensions method with string values
     */
    public function testAllowedExtensionsWithStringValues(): void
    {
        // Arrange
        $extensions = ['jpg', 'png'];

        // Act
        $result = $this->collector->allowedExtensions(...$extensions);
        $rules  = $this->collector->getRules();

        // Assert
        $this->assertSame($this->collector, $result);
        $this->assertContains('ext_in[' . $this->fieldName . ',jpg,png]', $rules);
    }

    /**
     * Test allowedMimeTypes method with enum values
     */
    public function testAllowedMimeTypesWithEnumValues(): void
    {
        // Arrange
        $mimeTypes = [AssetMimeType::IMAGE_JPEG, AssetMimeType::IMAGE_PNG];

        // Act
        $result = $this->collector->allowedMimeTypes(...$mimeTypes);
        $rules  = $this->collector->getRules();

        // Assert
        $this->assertSame($this->collector, $result);
        $this->assertContains('mime_in[' . $this->fieldName . ',image/jpeg,image/png]', $rules);
    }

    /**
     * Test allowedMimeTypes method with string values
     */
    public function testAllowedMimeTypesWithStringValues(): void
    {
        // Arrange
        $mimeTypes = ['image/jpeg', 'image/png'];

        // Act
        $result = $this->collector->allowedMimeTypes(...$mimeTypes);
        $rules  = $this->collector->getRules();

        // Assert
        $this->assertSame($this->collector, $result);
        $this->assertContains('mime_in[' . $this->fieldName . ',image/jpeg,image/png]', $rules);
    }

    /**
     * Test onlyKeepLatest method
     */
    public function testOnlyKeepLatest(): void
    {
        // Arrange
        $maxItems = 3;

        // Act
        $result = $this->collector->onlyKeepLatest($maxItems);
        $rules  = $this->collector->getRules();

        // Assert
        $this->assertSame($this->collector, $result);
        $this->assertIsCallable($rules[1]);
    }

    /**
     * Test maxFileCountValidationRule method with valid file count
     */
    public function testMaxFileCountValidationRuleWithValidCount(): void
    {
        // Arrange
        $maxFiles = 3;
        $params   = "{$this->fieldName},{$maxFiles}";
        $error    = null;

        // Mock request with files
        $request = $this->createMock(IncomingRequest::class);
        $request->method('getFileMultiple')
            ->willReturn([
                $this->createMock(File::class),
                $this->createMock(File::class),
            ]);

        // Replace Services::request() with our mock
        Services::injectMock('request', $request);

        // Act
        $result = ValidationRuleCollector::maxFileCountValidationRule(null, $params, [], $error, $this->fieldName);

        // Assert
        $this->assertTrue($result);
        $this->assertNull($error);
    }

    /**
     * Test maxFileCountValidationRule method with invalid file count
     */
    public function testMaxFileCountValidationRuleWithInvalidCount(): void
    {
        // Arrange
        $maxFiles = 1;
        $params   = "{$this->fieldName},{$maxFiles}";
        $error    = null;

        // Mock request with files
        $request = $this->createMock(IncomingRequest::class);
        $request->method('getFileMultiple')
            ->willReturn([
                $this->createMock(File::class),
                $this->createMock(File::class),
            ]);

        // Replace Services::request() with our mock
        Services::injectMock('request', $request);

        // Act
        $result = ValidationRuleCollector::maxFileCountValidationRule(null, $params, [], $error, $this->fieldName);

        // Assert
        $this->assertFalse($result);
        $this->assertNotNull($error, 'Error should not be null');
        $this->assertStringContainsString("cannot contain more than {$maxFiles} files", $error . '');
    }

    /**
     * Test setMaxFileSize method
     */
    public function testSetMaxFileSize(): void
    {
        // Arrange
        $maxSize = 1024 * 1024; // 1MB

        // Act
        $result = $this->collector->setMaxFileSize($maxSize);
        $rules  = $this->collector->getRules();

        // Assert
        $this->assertSame($this->collector, $result);
        $this->assertContains('max_size[' . $this->fieldName . ',1024]', $rules);
    }

    /**
     * Test singleFileCollection method
     */
    public function testSingleFileCollection(): void
    {
        // Act
        $result = $this->collector->singleFileCollection();
        $rules  = $this->collector->getRules();

        // Assert
        $this->assertSame($this->collector, $result);
        $this->assertContains('max_file_count[' . $this->fieldName . ',1]', $rules);
    }

    /**
     * Test setPathGenerator method
     */
    public function testSetPathGenerator(): void
    {
        // Arrange
        $pathGenerator = $this->createMock(PathGeneratorInterface::class);

        // Act
        $result = $this->collector->setPathGenerator($pathGenerator);

        // Assert
        $this->assertSame($this->collector, $result);
    }

    /**
     * Test setMaxImageDimensions method
     */
    public function testSetMaxImageDimensions(): void
    {
        // Arrange
        $width  = 800;
        $height = 600;

        // Act
        $result = $this->collector->setMaxImageDimensions($width, $height);
        $rules  = $this->collector->getRules();

        // Assert
        $this->assertSame($this->collector, $result);
        $this->assertContains('max_dims[' . $this->fieldName . ',' . $width . ',' . $height . ']', $rules);
    }

    /**
     * Test setMinImageDimensions method
     */
    public function testSetMinImageDimensions(): void
    {
        // Arrange
        $width  = 200;
        $height = 150;

        // Act
        $result = $this->collector->setMinImageDimensions($width, $height);
        $rules  = $this->collector->getRules();

        // Assert
        $this->assertSame($this->collector, $result);
        $this->assertContains('min_dims[' . $this->fieldName . ',' . $width . ',' . $height . ']', $rules);
    }

    /**
     * Test requireImage method
     */
    public function testRequireImage(): void
    {
        // Act
        $result = $this->collector->requireImage();
        $rules  = $this->collector->getRules();

        // Assert
        $this->assertSame($this->collector, $result);
        $this->assertContains('is_image[' . $this->fieldName . ']', $rules);
    }

    /**
     * Test getRules method
     */
    public function testGetRules(): void
    {
        // Arrange
        $this->collector->allowedExtensions('jpg', 'png')
            ->allowedMimeTypes('image/jpeg', 'image/png')
            ->setMaxFileSize(1024 * 1024)
            ->requireImage();

        // Act
        $rules = $this->collector->getRules();

        // Assert
        $this->assertIsArray($rules);
        $this->assertCount(5, $rules); // uploaded, ext_in, mime_in, max_size, is_image
        $this->assertContains('uploaded[' . $this->fieldName . ']', $rules);
        $this->assertContains('ext_in[' . $this->fieldName . ',jpg,png]', $rules);
        $this->assertContains('mime_in[' . $this->fieldName . ',image/jpeg,image/png]', $rules);
        $this->assertContains('max_size[' . $this->fieldName . ',1024]', $rules);
        $this->assertContains('is_image[' . $this->fieldName . ']', $rules);
    }
}
