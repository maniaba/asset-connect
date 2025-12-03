<?php

declare(strict_types=1);

namespace Tests\Pending;

use CodeIgniter\Files\File;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;
use Maniaba\AssetConnect\Exceptions\FileException;
use Maniaba\AssetConnect\Exceptions\InvalidArgumentException;
use Maniaba\AssetConnect\Pending\PendingAsset;
use Override;
use PHPUnit\Framework\MockObject\MockObject;
use stdClass;

/**
 * @internal
 */
final class PendingAssetTest extends CIUnitTestCase
{
    /**
     * @var File&MockObject
     */
    private MockObject $mockFile;

    /**
     * @var MockObject&UploadedFile
     */
    private MockObject $mockUploadedFile;

    private string $tempFilePath;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockFile         = $this->createMock(File::class);
        $this->mockUploadedFile = $this->createMock(UploadedFile::class);

        // Create a temporary file for testing
        $this->tempFilePath = tempnam(sys_get_temp_dir(), 'test_pending_asset_');
        file_put_contents($this->tempFilePath, 'test content');
    }

    #[Override]
    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up temporary file
        if (file_exists($this->tempFilePath)) {
            unlink($this->tempFilePath);
        }
    }

    /**
     * Test creating PendingAsset from File object
     */
    public function testCreateFromFileWithFileObject(): void
    {
        // Arrange
        $file = new File($this->tempFilePath);

        // Act
        $pendingAsset = PendingAsset::createFromFile($file);

        // Assert
        $this->assertInstanceOf(PendingAsset::class, $pendingAsset);
        $this->assertSame($file->getBasename(), $pendingAsset->file_name);
        $this->assertSame(pathinfo($file->getBasename(), PATHINFO_FILENAME), $pendingAsset->name);
        $this->assertIsString($pendingAsset->mime_type);
        $this->assertIsInt($pendingAsset->size);
    }

    /**
     * Test creating PendingAsset from file path string
     */
    public function testCreateFromFileWithStringPath(): void
    {
        // Act
        $pendingAsset = PendingAsset::createFromFile($this->tempFilePath);

        // Assert
        $this->assertInstanceOf(PendingAsset::class, $pendingAsset);
        $this->assertIsString($pendingAsset->file_name);
        $this->assertIsString($pendingAsset->name);
    }

    /**
     * Test creating PendingAsset from UploadedFile
     */
    public function testCreateFromFileWithUploadedFile(): void
    {
        // Arrange
        $this->mockUploadedFile->method('isFile')->willReturn(true);
        $this->mockUploadedFile->method('getClientName')->willReturn('uploaded_file.jpg');
        $this->mockUploadedFile->method('getMimeType')->willReturn('image/jpeg');
        $this->mockUploadedFile->method('getSize')->willReturn(1024);
        $this->mockUploadedFile->method('getCTime')->willReturn(time());
        $this->mockUploadedFile->method('getMTime')->willReturn(time());
        $this->mockUploadedFile->method('getRealPath')->willReturn($this->tempFilePath);

        // Act
        $pendingAsset = PendingAsset::createFromFile($this->mockUploadedFile);

        // Assert
        $this->assertInstanceOf(PendingAsset::class, $pendingAsset);
        $this->assertSame('uploaded_file.jpg', $pendingAsset->file_name);
        $this->assertSame('uploaded_file', $pendingAsset->name);
        $this->assertSame('image/jpeg', $pendingAsset->mime_type);
        $this->assertSame(1024, $pendingAsset->size);
    }

    /**
     * Test creating PendingAsset from non-existent file throws exception
     */
    public function testCreateFromFileWithNonExistentFileThrowsException(): void
    {
        // Arrange
        $this->mockFile->method('isFile')->willReturn(false);
        $this->mockFile->method('getRealPath')->willReturn('/path/to/nonexistent/file.txt');

        // Act & Assert
        $this->expectException(FileException::class);
        PendingAsset::createFromFile($this->mockFile);
    }

    /**
     * Test creating PendingAsset with attributes array
     */
    public function testCreateFromFileWithAttributesArray(): void
    {
        // Arrange
        $attributes = [
            'name'              => 'custom_name',
            'order'             => 5,
            'preserve_original' => true,
            'custom_properties' => ['key' => 'value'],
        ];

        // Act
        $pendingAsset = PendingAsset::createFromFile($this->tempFilePath, $attributes);

        // Assert
        $this->assertSame('custom_name', $pendingAsset->name);
        $this->assertSame(5, $pendingAsset->order);
        $this->assertTrue($pendingAsset->preserve_original);
        $this->assertSame(['key' => 'value'], $pendingAsset->custom_properties);
    }

    /**
     * Test creating PendingAsset with attributes JSON string
     */
    public function testCreateFromFileWithAttributesJsonString(): void
    {
        // Arrange
        $attributes = json_encode([
            'name'  => 'json_name',
            'order' => 10,
        ]);

        // Act
        $pendingAsset = PendingAsset::createFromFile($this->tempFilePath, $attributes);

        // Assert
        $this->assertSame('json_name', $pendingAsset->name);
        $this->assertSame(10, $pendingAsset->order);
    }

    /**
     * Test creating PendingAsset with invalid JSON string defaults to empty array
     */
    public function testCreateFromFileWithInvalidJsonStringDefaultsToEmptyArray(): void
    {
        // Arrange
        $invalidJson = '{invalid json}';

        // Act
        $pendingAsset = PendingAsset::createFromFile($this->tempFilePath, $invalidJson);

        // Assert - should use defaults
        $this->assertInstanceOf(PendingAsset::class, $pendingAsset);
        $this->assertSame(0, $pendingAsset->order);
        $this->assertFalse($pendingAsset->preserve_original);
    }

    /**
     * Test creating PendingAsset from base64 string
     */
    public function testCreateFromBase64(): void
    {
        // Arrange
        $content = 'test base64 content';
        $base64  = base64_encode($content);

        // Act
        $pendingAsset = PendingAsset::createFromBase64($base64);

        // Assert
        $this->assertInstanceOf(PendingAsset::class, $pendingAsset);
        $this->assertIsString($pendingAsset->file_name);
        $this->assertGreaterThan(0, $pendingAsset->size);
    }

    /**
     * Test creating PendingAsset from invalid base64 throws exception
     */
    public function testCreateFromBase64WithInvalidDataThrowsException(): void
    {
        // Arrange
        $invalidBase64 = 'not valid base64!!!';

        // Act & Assert
        $this->expectException(FileException::class);
        PendingAsset::createFromBase64($invalidBase64);
    }

    /**
     * Test creating PendingAsset from string
     */
    public function testCreateFromString(): void
    {
        // Arrange
        $content = 'test string content';

        // Act
        $pendingAsset = PendingAsset::createFromString($content);

        // Assert
        $this->assertInstanceOf(PendingAsset::class, $pendingAsset);
        $this->assertIsString($pendingAsset->file_name);
        $this->assertGreaterThan(0, $pendingAsset->size);
    }

    /**
     * Test creating PendingAsset from string with attributes
     */
    public function testCreateFromStringWithAttributes(): void
    {
        // Arrange
        $content    = 'test content';
        $attributes = ['name' => 'string_asset', 'order' => 3];

        // Act
        $pendingAsset = PendingAsset::createFromString($content, $attributes);

        // Assert
        $this->assertSame('string_asset', $pendingAsset->name);
        $this->assertSame(3, $pendingAsset->order);
    }

    /**
     * Test usingName method
     */
    public function testUsingName(): void
    {
        // Arrange
        $pendingAsset = PendingAsset::createFromFile($this->tempFilePath);

        // Act
        $result = $pendingAsset->usingName('new_name');

        // Assert
        $this->assertSame($pendingAsset, $result);
        $this->assertSame('new_name', $pendingAsset->name);
    }

    /**
     * Test usingFileName method
     */
    public function testUsingFileName(): void
    {
        // Arrange
        $pendingAsset = PendingAsset::createFromFile($this->tempFilePath);

        // Act
        $result = $pendingAsset->usingFileName('new_filename.txt');

        // Assert
        $this->assertSame($pendingAsset, $result);
        $this->assertSame('new_filename.txt', $pendingAsset->file_name);
    }

    /**
     * Test preservingOriginal method with default true
     */
    public function testPreservingOriginalWithDefaultTrue(): void
    {
        // Arrange
        $pendingAsset = PendingAsset::createFromFile($this->tempFilePath);

        // Act
        $result = $pendingAsset->preservingOriginal();

        // Assert
        $this->assertSame($pendingAsset, $result);
        $this->assertTrue($pendingAsset->preserve_original);
    }

    /**
     * Test preservingOriginal method with explicit false
     */
    public function testPreservingOriginalWithExplicitFalse(): void
    {
        // Arrange
        $pendingAsset = PendingAsset::createFromFile($this->tempFilePath);

        // Act
        $result = $pendingAsset->preservingOriginal(false);

        // Assert
        $this->assertSame($pendingAsset, $result);
        $this->assertFalse($pendingAsset->preserve_original);
    }

    /**
     * Test setOrder method
     */
    public function testSetOrder(): void
    {
        // Arrange
        $pendingAsset = PendingAsset::createFromFile($this->tempFilePath);

        // Act
        $result = $pendingAsset->setOrder(7);

        // Assert
        $this->assertSame($pendingAsset, $result);
        $this->assertSame(7, $pendingAsset->order);
    }

    /**
     * Test withCustomProperties method
     */
    public function testWithCustomProperties(): void
    {
        // Arrange
        $pendingAsset = PendingAsset::createFromFile($this->tempFilePath);
        $properties   = ['key1' => 'value1', 'key2' => 'value2'];

        // Act
        $result = $pendingAsset->withCustomProperties($properties);

        // Assert
        $this->assertSame($pendingAsset, $result);
        $this->assertSame($properties, $pendingAsset->custom_properties);
    }

    /**
     * Test withCustomProperty method
     */
    public function testWithCustomProperty(): void
    {
        // Arrange
        $pendingAsset = PendingAsset::createFromFile($this->tempFilePath);

        // Act
        $result = $pendingAsset->withCustomProperty('custom_key', 'custom_value');

        // Assert
        $this->assertSame($pendingAsset, $result);
        $this->assertSame('custom_value', $pendingAsset->custom_properties['custom_key']);
    }

    /**
     * Test withCustomProperty method adding multiple properties
     */
    public function testWithCustomPropertyAddingMultipleProperties(): void
    {
        // Arrange
        $pendingAsset = PendingAsset::createFromFile($this->tempFilePath);

        // Act
        $pendingAsset->withCustomProperty('key1', 'value1')
            ->withCustomProperty('key2', 'value2');

        // Assert
        $this->assertSame('value1', $pendingAsset->custom_properties['key1']);
        $this->assertSame('value2', $pendingAsset->custom_properties['key2']);
        $this->assertCount(2, $pendingAsset->custom_properties);
    }

    /**
     * Test setId method
     */
    public function testSetId(): void
    {
        // Arrange
        $pendingAsset = PendingAsset::createFromFile($this->tempFilePath);

        // Act
        $result = $pendingAsset->setId('test-id-123');

        // Assert
        $this->assertSame($pendingAsset, $result);
        $this->assertSame('test-id-123', $pendingAsset->id);
    }

    /**
     * Test setTTL method
     */
    public function testSetTTL(): void
    {
        // Arrange
        $pendingAsset = PendingAsset::createFromFile($this->tempFilePath);

        // Act
        $result = $pendingAsset->setTTL(3600);

        // Assert
        $this->assertSame($pendingAsset, $result);
        $this->assertSame(3600, $pendingAsset->ttl);
    }

    /**
     * Test jsonSerialize method
     */
    public function testJsonSerialize(): void
    {
        // Arrange
        $pendingAsset = PendingAsset::createFromFile($this->tempFilePath)
            ->usingName('serializable_asset')
            ->setOrder(5)
            ->preservingOriginal()
            ->setId('json-id')
            ->setTTL(7200)
            ->withCustomProperties(['test' => 'value']);

        // Act
        $json = $pendingAsset->jsonSerialize();

        // Assert
        $this->assertIsArray($json);
        $this->assertSame('json-id', $json['id']);
        $this->assertSame('serializable_asset', $json['name']);
        $this->assertSame(5, $json['order']);
        $this->assertTrue($json['preserve_original']);
        $this->assertSame(7200, $json['ttl']);
        $this->assertSame(['test' => 'value'], $json['custom_properties']);
        $this->assertArrayHasKey('file_name', $json);
        $this->assertArrayHasKey('mime_type', $json);
        $this->assertArrayHasKey('size', $json);
        $this->assertArrayHasKey('size_human_readable', $json);
        $this->assertArrayHasKey('created_at', $json);
        $this->assertArrayHasKey('updated_at', $json);
    }

    /**
     * Test magic __get method for existing properties
     */
    public function testMagicGetForExistingProperties(): void
    {
        // Arrange
        $pendingAsset = PendingAsset::createFromFile($this->tempFilePath);
        $pendingAsset->setId('magic-id');

        // Act
        $id = $pendingAsset->id;

        // Assert
        $this->assertSame('magic-id', $id);
    }

    /**
     * Test magic __get method for non-existing properties returns null
     */
    public function testMagicGetForNonExistingPropertiesReturnsNull(): void
    {
        // Arrange
        $pendingAsset = PendingAsset::createFromFile($this->tempFilePath);

        /** @phpstan-ignore-next-line Act */
        $nonExistent = $pendingAsset->nonExistentProperty;

        // Assert
        $this->assertNull($nonExistent);
    }

    /**
     * Test default values on creation
     */
    public function testDefaultValuesOnCreation(): void
    {
        // Act
        $pendingAsset = PendingAsset::createFromFile($this->tempFilePath);

        // Assert
        $this->assertSame('', $pendingAsset->id);
        $this->assertSame(0, $pendingAsset->order);
        $this->assertFalse($pendingAsset->preserve_original);
        $this->assertSame([], $pendingAsset->custom_properties);
        $this->assertSame(0, $pendingAsset->ttl);
        $this->assertInstanceOf(Time::class, $pendingAsset->created_at);
        $this->assertInstanceOf(Time::class, $pendingAsset->updated_at);
    }

    /**
     * Test getHumanReadableSize from AssetFileInfoTrait
     */
    public function testGetHumanReadableSize(): void
    {
        // Arrange
        $pendingAsset = PendingAsset::createFromFile($this->tempFilePath);

        // Act
        $humanReadable = $pendingAsset->getHumanReadableSize();

        // Assert
        $this->assertIsString($humanReadable);
        $this->assertStringContainsString('B', $humanReadable); // Should contain bytes unit
    }

    /**
     * Test fluent interface chaining
     */
    public function testFluentInterfaceChaining(): void
    {
        // Arrange
        $pendingAsset = PendingAsset::createFromFile($this->tempFilePath);

        // Act
        $result = $pendingAsset
            ->usingName('chained_asset')
            ->usingFileName('chained.txt')
            ->setOrder(99)
            ->preservingOriginal()
            ->withCustomProperties(['chain' => 'test'])
            ->withCustomProperty('another', 'property');

        // Assert
        $this->assertSame($pendingAsset, $result);
        $this->assertSame('chained_asset', $pendingAsset->name);
        $this->assertSame('chained.txt', $pendingAsset->file_name);
        $this->assertSame(99, $pendingAsset->order);
        $this->assertTrue($pendingAsset->preserve_original);
        $this->assertArrayHasKey('chain', $pendingAsset->custom_properties);
        $this->assertArrayHasKey('another', $pendingAsset->custom_properties);
    }

    /**
     * Test that attributes with wrong types are ignored during construction
     */
    public function testAttributesWithWrongTypesAreIgnored(): void
    {
        // Arrange
        $attributes = [
            'name'  => 'valid_name',
            'order' => 'should_be_int', // Wrong type
            'size'  => 'wrong_type',    // Wrong type
        ];

        // Act
        $pendingAsset = PendingAsset::createFromFile($this->tempFilePath, $attributes);

        // Assert
        $this->assertSame('valid_name', $pendingAsset->name);
        // order should remain default because of type error
        $this->assertSame(0, $pendingAsset->order);
    }

    /**
     * Test accessing file property
     */
    public function testAccessingFileProperty(): void
    {
        // Arrange
        $file         = new File($this->tempFilePath);
        $pendingAsset = PendingAsset::createFromFile($file);

        // Act
        $fileProperty = $pendingAsset->file;

        // Assert
        $this->assertInstanceOf(File::class, $fileProperty);
    }

    /**
     * Test createFromRequest with no key names throws exception
     */
    public function testCreateFromRequestWithNoKeyNamesThrowsException(): void
    {
        // Act & Assert
        $this->expectException(InvalidArgumentException::class);
        PendingAsset::createFromRequest();
    }

    /**
     * Test createFromRequest with single file field
     */
    public function testCreateFromRequestWithSingleFileField(): void
    {
        // Arrange
        $mockFile = $this->createMock(UploadedFile::class);
        $mockFile->method('isValid')->willReturn(true);
        $mockFile->method('isFile')->willReturn(true);
        $mockFile->method('getClientName')->willReturn('uploaded.jpg');
        $mockFile->method('getMimeType')->willReturn('image/jpeg');
        $mockFile->method('getSize')->willReturn(2048);
        $mockFile->method('getCTime')->willReturn(time());
        $mockFile->method('getMTime')->willReturn(time());
        $mockFile->method('getRealPath')->willReturn($this->tempFilePath);

        $mockRequest = $this->createMock(IncomingRequest::class);
        $mockRequest->method('getFiles')->willReturn([
            'avatar' => $mockFile,
        ]);

        Services::injectMock('request', $mockRequest);

        // Act
        $result = PendingAsset::createFromRequest('avatar');

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('avatar', $result);
        $this->assertCount(1, $result['avatar']);
        $this->assertInstanceOf(PendingAsset::class, $result['avatar'][0]);
        $this->assertSame('uploaded.jpg', $result['avatar'][0]->file_name);
    }

    /**
     * Test createFromRequest with multiple files in single field
     */
    public function testCreateFromRequestWithMultipleFilesInSingleField(): void
    {
        // Arrange
        $mockFile1 = $this->createMock(UploadedFile::class);
        $mockFile1->method('isValid')->willReturn(true);
        $mockFile1->method('isFile')->willReturn(true);
        $mockFile1->method('getClientName')->willReturn('file1.jpg');
        $mockFile1->method('getMimeType')->willReturn('image/jpeg');
        $mockFile1->method('getSize')->willReturn(1024);
        $mockFile1->method('getCTime')->willReturn(time());
        $mockFile1->method('getMTime')->willReturn(time());
        $mockFile1->method('getRealPath')->willReturn($this->tempFilePath);

        $mockFile2 = $this->createMock(UploadedFile::class);
        $mockFile2->method('isValid')->willReturn(true);
        $mockFile2->method('isFile')->willReturn(true);
        $mockFile2->method('getClientName')->willReturn('file2.jpg');
        $mockFile2->method('getMimeType')->willReturn('image/jpeg');
        $mockFile2->method('getSize')->willReturn(2048);
        $mockFile2->method('getCTime')->willReturn(time());
        $mockFile2->method('getMTime')->willReturn(time());
        $mockFile2->method('getRealPath')->willReturn($this->tempFilePath);

        $mockRequest = $this->createMock(IncomingRequest::class);
        $mockRequest->method('getFiles')->willReturn([
            'documents' => [$mockFile1, $mockFile2],
        ]);

        Services::injectMock('request', $mockRequest);

        // Act
        $result = PendingAsset::createFromRequest('documents');

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('documents', $result);
        $this->assertCount(2, $result['documents']);
        $this->assertInstanceOf(PendingAsset::class, $result['documents'][0]);
        $this->assertInstanceOf(PendingAsset::class, $result['documents'][1]);
        $this->assertSame('file1.jpg', $result['documents'][0]->file_name);
        $this->assertSame('file2.jpg', $result['documents'][1]->file_name);
    }

    /**
     * Test createFromRequest with multiple field names
     */
    public function testCreateFromRequestWithMultipleFieldNames(): void
    {
        // Arrange
        $mockFile1 = $this->createMock(UploadedFile::class);
        $mockFile1->method('isValid')->willReturn(true);
        $mockFile1->method('isFile')->willReturn(true);
        $mockFile1->method('getClientName')->willReturn('avatar.jpg');
        $mockFile1->method('getMimeType')->willReturn('image/jpeg');
        $mockFile1->method('getSize')->willReturn(1024);
        $mockFile1->method('getCTime')->willReturn(time());
        $mockFile1->method('getMTime')->willReturn(time());
        $mockFile1->method('getRealPath')->willReturn($this->tempFilePath);

        $mockFile2 = $this->createMock(UploadedFile::class);
        $mockFile2->method('isValid')->willReturn(true);
        $mockFile2->method('isFile')->willReturn(true);
        $mockFile2->method('getClientName')->willReturn('cover.jpg');
        $mockFile2->method('getMimeType')->willReturn('image/jpeg');
        $mockFile2->method('getSize')->willReturn(2048);
        $mockFile2->method('getCTime')->willReturn(time());
        $mockFile2->method('getMTime')->willReturn(time());
        $mockFile2->method('getRealPath')->willReturn($this->tempFilePath);

        $mockRequest = $this->createMock(IncomingRequest::class);
        $mockRequest->method('getFiles')->willReturn([
            'avatar' => $mockFile1,
            'cover'  => $mockFile2,
            'other'  => $this->createMock(UploadedFile::class), // This should be ignored
        ]);

        Services::injectMock('request', $mockRequest);

        // Act
        $result = PendingAsset::createFromRequest('avatar', 'cover');

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('avatar', $result);
        $this->assertArrayHasKey('cover', $result);
        $this->assertArrayNotHasKey('other', $result);
        $this->assertCount(1, $result['avatar']);
        $this->assertCount(1, $result['cover']);
        $this->assertSame('avatar.jpg', $result['avatar'][0]->file_name);
        $this->assertSame('cover.jpg', $result['cover'][0]->file_name);
    }

    /**
     * Test createFromRequest with invalid file throws exception
     */
    public function testCreateFromRequestWithInvalidFileThrowsException(): void
    {
        // Arrange
        $mockFile = $this->createMock(UploadedFile::class);
        $mockFile->method('isValid')->willReturn(false);

        $mockRequest = $this->createMock(IncomingRequest::class);
        $mockRequest->method('getFiles')->willReturn([
            'avatar' => $mockFile,
        ]);

        Services::injectMock('request', $mockRequest);

        // Act & Assert
        $this->expectException(FileException::class);
        PendingAsset::createFromRequest('avatar');
    }

    /**
     * Test createFromRequest with non-UploadedFile instance throws exception
     */
    public function testCreateFromRequestWithNonUploadedFileThrowsException(): void
    {
        // Arrange
        $mockRequest = $this->createMock(IncomingRequest::class);
        $mockRequest->method('getFiles')->willReturn([
            'avatar' => new stdClass(), // Not an UploadedFile
        ]);

        Services::injectMock('request', $mockRequest);

        // Act & Assert
        $this->expectException(FileException::class);
        PendingAsset::createFromRequest('avatar');
    }

    /**
     * Test createFromRequest with non-existent field returns empty array
     */
    public function testCreateFromRequestWithNonExistentFieldReturnsEmptyArray(): void
    {
        // Arrange
        $mockRequest = $this->createMock(IncomingRequest::class);
        $mockRequest->method('getFiles')->willReturn([
            'avatar' => $this->createMock(UploadedFile::class),
        ]);

        Services::injectMock('request', $mockRequest);

        // Act
        $result = PendingAsset::createFromRequest('nonexistent');

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test createFromRequest when request has no files
     */
    public function testCreateFromRequestWhenRequestHasNoFiles(): void
    {
        // Arrange
        $mockRequest = $this->createMock(IncomingRequest::class);
        $mockRequest->method('getFiles')->willReturn([]);

        Services::injectMock('request', $mockRequest);

        // Act
        $result = PendingAsset::createFromRequest('avatar');

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test createFromRequest with mixed single and multiple files
     */
    public function testCreateFromRequestWithMixedSingleAndMultipleFiles(): void
    {
        // Arrange
        $mockFileSingle = $this->createMock(UploadedFile::class);
        $mockFileSingle->method('isValid')->willReturn(true);
        $mockFileSingle->method('isFile')->willReturn(true);
        $mockFileSingle->method('getClientName')->willReturn('single.jpg');
        $mockFileSingle->method('getMimeType')->willReturn('image/jpeg');
        $mockFileSingle->method('getSize')->willReturn(1024);
        $mockFileSingle->method('getCTime')->willReturn(time());
        $mockFileSingle->method('getMTime')->willReturn(time());
        $mockFileSingle->method('getRealPath')->willReturn($this->tempFilePath);

        $mockFileMultiple1 = $this->createMock(UploadedFile::class);
        $mockFileMultiple1->method('isValid')->willReturn(true);
        $mockFileMultiple1->method('isFile')->willReturn(true);
        $mockFileMultiple1->method('getClientName')->willReturn('multi1.jpg');
        $mockFileMultiple1->method('getMimeType')->willReturn('image/jpeg');
        $mockFileMultiple1->method('getSize')->willReturn(2048);
        $mockFileMultiple1->method('getCTime')->willReturn(time());
        $mockFileMultiple1->method('getMTime')->willReturn(time());
        $mockFileMultiple1->method('getRealPath')->willReturn($this->tempFilePath);

        $mockFileMultiple2 = $this->createMock(UploadedFile::class);
        $mockFileMultiple2->method('isValid')->willReturn(true);
        $mockFileMultiple2->method('isFile')->willReturn(true);
        $mockFileMultiple2->method('getClientName')->willReturn('multi2.jpg');
        $mockFileMultiple2->method('getMimeType')->willReturn('image/jpeg');
        $mockFileMultiple2->method('getSize')->willReturn(3072);
        $mockFileMultiple2->method('getCTime')->willReturn(time());
        $mockFileMultiple2->method('getMTime')->willReturn(time());
        $mockFileMultiple2->method('getRealPath')->willReturn($this->tempFilePath);

        $mockRequest = $this->createMock(IncomingRequest::class);
        $mockRequest->method('getFiles')->willReturn([
            'avatar'    => $mockFileSingle,
            'documents' => [$mockFileMultiple1, $mockFileMultiple2],
        ]);

        Services::injectMock('request', $mockRequest);

        // Act
        $result = PendingAsset::createFromRequest('avatar', 'documents');

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('avatar', $result);
        $this->assertArrayHasKey('documents', $result);
        $this->assertCount(1, $result['avatar']);
        $this->assertCount(2, $result['documents']);
        $this->assertSame('single.jpg', $result['avatar'][0]->file_name);
        $this->assertSame('multi1.jpg', $result['documents'][0]->file_name);
        $this->assertSame('multi2.jpg', $result['documents'][1]->file_name);
    }
}
