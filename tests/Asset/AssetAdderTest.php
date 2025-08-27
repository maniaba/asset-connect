<?php

declare(strict_types=1);

namespace Tests\Asset;

use CodeIgniter\Entity\Entity;
use CodeIgniter\Files\File;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\ReflectionHelper;
use Maniaba\AssetConnect\Asset\Asset;
use Maniaba\AssetConnect\Asset\AssetAdder;
use Maniaba\AssetConnect\Asset\AssetMetadata;
use Maniaba\AssetConnect\AssetCollection\SetupAssetCollection;
use Maniaba\AssetConnect\Contracts\AssetConnectEntityInterface;
use Maniaba\AssetConnect\Exceptions\AssetException;
use Maniaba\AssetConnect\Traits\UseAssetConnectTrait;
use Override;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;
use Tests\Support\TestEntity;

/**
 * @internal
 */
final class AssetAdderTest extends CIUnitTestCase
{
    use ReflectionHelper;

    private AssetConnectEntityInterface&Entity $mockEntity;

    /**
     * @var File&MockObject
     */
    private MockObject $mockFile;

    /**
     * @var MockObject&UploadedFile
     */
    private MockObject $mockUploadedFile;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        // Create mock entity with UseAssetConnectTrait
        $this->mockEntity = $this->createMockEntityWithTrait();

        // Create mock file
        $this->mockFile = $this->createMock(File::class);
        $this->mockFile->method('getRealPath')->willReturn('/tmp/test-file.txt');
        $this->mockFile->method('getBasename')->willReturn('test-file.txt');
        $this->mockFile->method('getMimeType')->willReturn('text/plain');
        $this->mockFile->method('getSize')->willReturn(1024);

        // Create mock uploaded file
        $this->mockUploadedFile = $this->createMock(UploadedFile::class);
        $this->mockUploadedFile->method('getRealPath')->willReturn('/tmp/uploaded-file.txt');
        $this->mockUploadedFile->method('getClientName')->willReturn('uploaded-file.txt');
        $this->mockUploadedFile->method('getMimeType')->willReturn('text/plain');
        $this->mockUploadedFile->method('getSize')->willReturn(2048);
    }

    public function testConstructorWithValidEntityAndFile(): void
    {
        $assetAdder = new AssetAdder($this->mockEntity, $this->mockFile);

        $this->assertInstanceOf(AssetAdder::class, $assetAdder);
    }

    public function testConstructorWithStringFilePath(): void
    {
        // Create a temporary file for testing
        $tempFile = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($tempFile, 'test content');

        $assetAdder = new AssetAdder($this->mockEntity, $tempFile);

        $this->assertInstanceOf(AssetAdder::class, $assetAdder);

        // Clean up
        unlink($tempFile);
    }

    public function testConstructorWithUploadedFile(): void
    {
        $assetAdder = new AssetAdder($this->mockEntity, $this->mockUploadedFile);

        $this->assertInstanceOf(AssetAdder::class, $assetAdder);
    }

    public function testConstructorThrowsExceptionForInvalidEntity(): void
    {
        $invalidEntity = $this->createMock(Entity::class);

        $this->expectException(AssetException::class);

        /** @phpstan-ignore-next-line We know this is invalid for testing */
        new AssetAdder($invalidEntity, $this->mockFile);
    }

    public function testPreservingOriginalWithTrue(): void
    {
        $assetAdder = new AssetAdder($this->mockEntity, $this->mockFile);

        $result = $assetAdder->preservingOriginal(true);

        $this->assertSame($assetAdder, $result);
    }

    public function testPreservingOriginalWithFalse(): void
    {
        $assetAdder = new AssetAdder($this->mockEntity, $this->mockFile);

        $result = $assetAdder->preservingOriginal(false);

        $this->assertSame($assetAdder, $result);
    }

    public function testPreservingOriginalWithDefaultValue(): void
    {
        $assetAdder = new AssetAdder($this->mockEntity, $this->mockFile);

        $result = $assetAdder->preservingOriginal();

        $this->assertSame($assetAdder, $result);
    }

    public function testSetOrder(): void
    {
        $assetAdder = new AssetAdder($this->mockEntity, $this->mockFile);

        $result = $assetAdder->setOrder(5);

        $this->assertSame($assetAdder, $result);

        // Verify the order was set on the asset
        $asset = self::getPrivateProperty($assetAdder, 'asset');
        $this->assertSame(5, $asset->order);
    }

    public function testUsingFileName(): void
    {
        $assetAdder = new AssetAdder($this->mockEntity, $this->mockFile);

        $result = $assetAdder->usingFileName('custom-file-name.txt');

        $this->assertSame($assetAdder, $result);

        // Verify the file name was set on the asset
        $asset = self::getPrivateProperty($assetAdder, 'asset');
        $this->assertSame('custom-file-name.txt', $asset->file_name);
    }

    public function testUsingName(): void
    {
        $assetAdder = new AssetAdder($this->mockEntity, $this->mockFile);

        $result = $assetAdder->usingName('Custom Asset Name');

        $this->assertSame($assetAdder, $result);

        // Verify the name was set on the asset
        $asset = self::getPrivateProperty($assetAdder, 'asset');
        $this->assertSame('Custom Asset Name', $asset->name);
    }

    public function testSanitizingFileName(): void
    {
        $assetAdder = new AssetAdder($this->mockEntity, $this->mockFile);

        $customSanitizer = static fn (string $fileName): string => strtoupper($fileName);
        $result          = $assetAdder->sanitizingFileName($customSanitizer);

        $this->assertSame($assetAdder, $result);

        // Verify the sanitizer was set
        $sanitizer = self::getPrivateProperty($assetAdder, 'fileNameSanitizer');
        $this->assertSame('TEST.TXT', $sanitizer('test.txt'));
    }

    public function testWithCustomProperty(): void
    {
        $assetAdder = new AssetAdder($this->mockEntity, $this->mockFile);

        $result = $assetAdder->withCustomProperty('custom_key', 'custom_value');

        $this->assertSame($assetAdder, $result);

        // Verify the custom property was set
        $asset = self::getPrivateProperty($assetAdder, 'asset');
        $this->assertSame('custom_value', $asset->metadata->userCustom->get('custom_key'));
    }

    public function testWithCustomProperties(): void
    {
        $assetAdder = new AssetAdder($this->mockEntity, $this->mockFile);

        $customProperties = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => ['nested', 'array'],
        ];

        $result = $assetAdder->withCustomProperties($customProperties);

        $this->assertSame($assetAdder, $result);

        // Verify all custom properties were set
        $asset = self::getPrivateProperty($assetAdder, 'asset');
        $this->assertSame('value1', $asset->metadata->userCustom->get('key1'));
        $this->assertSame('value2', $asset->metadata->userCustom->get('key2'));
        $this->assertSame(['nested', 'array'], $asset->metadata->userCustom->get('key3'));
    }

    public function testWithCustomPropertiesEmptyArray(): void
    {
        $assetAdder = new AssetAdder($this->mockEntity, $this->mockFile);

        $result = $assetAdder->withCustomProperties([]);

        $this->assertSame($assetAdder, $result);
    }

    public function testMetadata(): void
    {
        $assetAdder = new AssetAdder($this->mockEntity, $this->mockFile);

        $metadata = $assetAdder->metadata();

        $this->assertInstanceOf(AssetMetadata::class, $metadata);
    }

    public function testToAssetCollectionMethodExists(): void
    {
        $assetAdder = new AssetAdder($this->mockEntity, $this->mockFile);

        // Test that the method exists and can be called (integration testing would require full setup)
        $this->assertTrue(method_exists($assetAdder, 'toAssetCollection'));
    }

    public function testToAssetCollectionWithStringCollection(): void
    {
        $assetAdder = new AssetAdder($this->mockEntity, $this->mockFile);

        // Test that the method can accept a string collection parameter
        $this->assertTrue(method_exists($assetAdder, 'toAssetCollection'));

        // Use reflection to verify the method signature
        $reflection = new ReflectionClass($assetAdder);
        $method     = $reflection->getMethod('toAssetCollection');
        $this->assertTrue($method->isPublic());
    }

    public function testToAssetCollectionWithCollectionDefinitionInterface(): void
    {
        $assetAdder = new AssetAdder($this->mockEntity, $this->mockFile);

        // Test that the method exists and accepts the proper parameter types
        $reflection = new ReflectionClass($assetAdder);
        $method     = $reflection->getMethod('toAssetCollection');
        $parameters = $method->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertSame('collection', $parameters[0]->getName());
    }

    /**
     * Test method chaining functionality
     */
    public function testMethodChaining(): void
    {
        $assetAdder = new AssetAdder($this->mockEntity, $this->mockFile);

        $result = $assetAdder
            ->usingName('Test Asset')
            ->usingFileName('test-file.txt')
            ->setOrder(10)
            ->preservingOriginal(true)
            ->withCustomProperty('test_key', 'test_value')
            ->withCustomProperties(['key1' => 'value1', 'key2' => 'value2']);

        $this->assertSame($assetAdder, $result);

        // Verify all properties were set correctly
        $asset = self::getPrivateProperty($assetAdder, 'asset');
        $this->assertSame('Test Asset', $asset->name);
        $this->assertSame('test-file.txt', $asset->file_name);
        $this->assertSame(10, $asset->order);
        $this->assertSame('test_value', $asset->metadata->userCustom->get('test_key'));
        $this->assertSame('value1', $asset->metadata->userCustom->get('key1'));
        $this->assertSame('value2', $asset->metadata->userCustom->get('key2'));
    }

    public function testSetFileWithStringPath(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($tempFile, 'test content');

        // Test constructor with string path (covers setFile method)
        $assetAdder = new AssetAdder($this->mockEntity, $tempFile);

        $asset = self::getPrivateProperty($assetAdder, 'asset');
        $this->assertInstanceOf(Asset::class, $asset);
        $this->assertSame(basename($tempFile), $asset->file_name);

        // Clean up
        unlink($tempFile);
    }

    public function testSetFileWithUploadedFile(): void
    {
        // Test constructor with uploaded file (covers setFile method with UploadedFile)
        $assetAdder = new AssetAdder($this->mockEntity, $this->mockUploadedFile);

        $asset = self::getPrivateProperty($assetAdder, 'asset');
        $this->assertInstanceOf(Asset::class, $asset);
        $this->assertSame('uploaded-file.txt', $asset->file_name);
    }

    public function testAssetPropertiesAreSetCorrectly(): void
    {
        $assetAdder = new AssetAdder($this->mockEntity, $this->mockFile);

        $asset = self::getPrivateProperty($assetAdder, 'asset');

        // Test that all asset properties are set correctly
        $this->assertSame('/tmp/test-file.txt', $asset->path);
        $this->assertSame('test-file.txt', $asset->file_name);
        $this->assertSame('test-file', $asset->name); // filename without extension
        $this->assertSame('text/plain', $asset->mime_type);
        $this->assertSame(1024, $asset->size);
        $this->assertSame(0, $asset->order); // default order
        $this->assertSame($this->mockFile, $asset->file);
        // Test md5 hash
        $this->assertSame(md5($this->mockEntity::class), $asset->entity_type, 'Entity type should match the hash of the entity class name');
        $this->assertSame(123, $asset->entity_id, "Entity ID should match the mock entity's ID");
    }

    public function testPrivateSetFileMethodWithDifferentFileTypes(): void
    {
        // Test with regular File
        $assetAdder1 = new AssetAdder($this->mockEntity, $this->mockFile);
        $asset1      = self::getPrivateProperty($assetAdder1, 'asset');
        $this->assertSame('test-file.txt', $asset1->file_name);

        // Test with UploadedFile
        $assetAdder2 = new AssetAdder($this->mockEntity, $this->mockUploadedFile);
        $asset2      = self::getPrivateProperty($assetAdder2, 'asset');
        $this->assertSame('uploaded-file.txt', $asset2->file_name);
    }

    public function testFileNameExtractionFromPath(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($tempFile, 'test content');

        $assetAdder = new AssetAdder($this->mockEntity, $tempFile);
        $asset      = self::getPrivateProperty($assetAdder, 'asset');

        // Should extract filename from path
        $expectedName = pathinfo(basename($tempFile), PATHINFO_FILENAME);
        $this->assertSame($expectedName, $asset->name);

        unlink($tempFile);
    }

    public function testEntityIdIsSetOnAsset(): void
    {
        $assetAdder = new AssetAdder($this->mockEntity, $this->mockFile);
        $asset      = self::getPrivateProperty($assetAdder, 'asset');

        // Should set entity_id from the entity's primary key
        $this->assertSame(123, $asset->entity_id);
    }

    public function testSetupAssetCollectionIsInitialized(): void
    {
        $assetAdder           = new AssetAdder($this->mockEntity, $this->mockFile);
        $setupAssetCollection = self::getPrivateProperty($assetAdder, 'setupAssetCollection');

        $this->assertInstanceOf(SetupAssetCollection::class, $setupAssetCollection);
    }

    public function testFileNameSanitizerIsSet(): void
    {
        $assetAdder = new AssetAdder($this->mockEntity, $this->mockFile);
        $sanitizer  = self::getPrivateProperty($assetAdder, 'fileNameSanitizer');

        $this->assertIsCallable($sanitizer);

        // Test default sanitizer (should return input unchanged in our mock)
        $result = $sanitizer('test-file.txt');
        $this->assertSame('test-file.txt', $result);
    }

    /**
     * Helper method to create a mock entity with UseAssetConnectTrait
     */
    private function createMockEntityWithTrait(): AssetConnectEntityInterface&Entity
    {
        return new TestEntity();
    }
}
