<?php

declare(strict_types=1);

namespace Tests\Asset;

use CodeIgniter\Config\Factories;
use CodeIgniter\Config\Services;
use CodeIgniter\Entity\Entity;
use CodeIgniter\Files\File;
use CodeIgniter\HTTP\DownloadResponse;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;
use Maniaba\AssetConnect\Asset\Asset;
use Maniaba\AssetConnect\Asset\AssetMetadata;
use Maniaba\AssetConnect\Asset\Interfaces\AssetCollectionDefinitionInterface;
use Maniaba\AssetConnect\AssetCollection\DefaultAssetCollection;
use Maniaba\AssetConnect\AssetVariants\AssetVariant;
use Maniaba\AssetConnect\Config\Asset as AssetConfig;
use Maniaba\AssetConnect\Enums\AssetVisibility;
use Maniaba\AssetConnect\Exceptions\AssetException;
use Maniaba\AssetConnect\Exceptions\FileException;
use Maniaba\AssetConnect\Exceptions\InvalidArgumentException as AssetInvalidArgumentException;
use Maniaba\AssetConnect\Models\AssetModel;
use Maniaba\AssetConnect\Services\Interfaces\AssetAccessServiceInterface;
use Maniaba\AssetConnect\Storage\Interfaces\StorageDiskInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use RuntimeException;
use Tests\Support\AssetCollections\ProtectedTestAssetCollection;
use Tests\Support\Config\TestAssetConfig;
use Tests\Support\Models\FailingSaveAssetModel;
use Tests\Support\Models\InvalidMagicReturnTypeAssetModel;
use Tests\Support\TestEntity;

/**
 * @internal
 */
final class AssetTest extends CIUnitTestCase
{
    private Asset $asset;

    /**
     * @var File&Stub
     */
    private Stub $mockFile;

    /**
     * @var Entity&Stub
     */
    private Stub $mockEntity;

    /**
     * @var AssetCollectionDefinitionInterface&Stub
     */
    private Stub $mockCollectionDefinition;

    protected function setUp(): void
    {
        parent::setUp();
        $this->asset                    = new Asset();
        $this->mockFile                 = $this->createStub(File::class);
        $this->mockEntity               = $this->createStub(Entity::class);
        $this->mockCollectionDefinition = $this->createStub(AssetCollectionDefinitionInterface::class);
        // Setup global function mocks
        $this->setupGlobalFunctionMocks();
    }

    /**
     * Setup global function mocks
     */
    private function setupGlobalFunctionMocks(): void
    {
        // Mock AssetCollectionDefinitionFactory::validateStringClass
        global $mockFunctions;
        $mockFunctions['Maniaba\AssetConnect\AssetCollection\AssetCollectionDefinitionFactory::validateStringClass'] = static fn () => null;

        // For testCreateWithInvalidReturnType
        $mockFunctions['Maniaba\AssetConnect\Models\AssetModel::init'] = null;
    }

    private function configureRemoteDisk(string $expectedPath, string $contents): MockObject&StorageDiskInterface
    {
        $stream = $this->streamFromString($contents);

        $disk = $this->createMock(StorageDiskInterface::class);
        $disk->method('name')->willReturn('remote');
        $disk->method('visibility')->willReturn(AssetVisibility::PUBLIC);
        $disk->method('localPath')->willReturn(null);
        $disk->expects($this->once())
            ->method('fileExists')
            ->with($expectedPath)
            ->willReturn(true);
        $disk->expects($this->once())
            ->method('readStream')
            ->with($expectedPath)
            ->willReturn($stream);

        $config                     = new TestAssetConfig();
        $config->storages['remote'] = [
            'disk' => $disk,
        ];

        Factories::injectMock('config', AssetConfig::class, $config);
        Factories::injectMock('config', 'Asset', $config);

        return $disk;
    }

    /**
     * @return resource
     */
    private function streamFromString(string $contents)
    {
        $stream = fopen('php://temp', 'rb+');
        $this->assertIsResource($stream);
        fwrite($stream, $contents);
        rewind($stream);

        return $stream;
    }

    /**
     * @param array<string, StorageDiskInterface> $disks
     */
    private function configureStorageDisks(array $disks): void
    {
        $config = new TestAssetConfig();

        foreach ($disks as $name => $disk) {
            $config->storages[$name] = [
                'disk' => $disk,
            ];
        }

        Factories::injectMock('config', AssetConfig::class, $config);
        Factories::injectMock('config', 'Asset', $config);
    }

    /**
     * Test setting entity type with an Entity instance
     */
    public function testSetEntityTypeWithEntityInstance(): void
    {
        // Arrange
        $this->mockEntity                                       = $this->createStub(Entity::class);
        $config                                                 = config('Asset');
        $config->entityKeyDefinitions[$this->mockEntity::class] = 'mock_entity';

        // Act
        $result = $this->asset->setEntityType($this->mockEntity);

        // Assert
        $this->assertSame($this->asset, $result);
        $this->assertSame('mock_entity', $this->asset->entity_type);
        $this->assertSame($this->mockEntity::class, $this->asset->subject_entity_class, 'The subject_entity_class should be set to the correct class name.');
    }

    /**
     * Test setting entity type with a class name
     */
    public function testSetEntityTypeWithClassName(): void
    {
        // Arrange
        $entityClass = Entity::class;

        Factories::injectMock('config', AssetConfig::class, new TestAssetConfig());

        // Act
        $result = $this->asset->setEntityType($entityClass);

        // Assert
        $this->assertSame($this->asset, $result);
        $this->assertSame('basic_entity', $this->asset->entity_type);
        $this->assertSame($entityClass, $this->asset->subject_entity_class, 'The subject_entity_class should be set to the correct class name.');
    }

    public function testSetEntityTypeWithStringAliasName(): void
    {
        // Arrange
        $entityClass = TestEntity::class;

        Factories::injectMock('config', AssetConfig::class, new TestAssetConfig());

        // Act
        $result = $this->asset->setEntityType('test_entity');

        // Assert
        $this->assertSame($this->asset, $result);
        $this->assertSame('test_entity', $this->asset->entity_type, 'The entity_type should be set to the alias name.');
        $this->assertSame($entityClass, $this->asset->subject_entity_class, 'The subject_entity_class should be set to the correct class name.');
    }

    public function testIsProtectedCollectionUsesStorageDiskVisibility(): void
    {
        $config = new TestAssetConfig();

        Factories::injectMock('config', AssetConfig::class, $config);
        Factories::injectMock('config', 'Asset', $config);

        $asset = new Asset([
            'storage' => 'protected',
        ]);

        $this->assertTrue($asset->is_protected_collection);
    }

    public function testCopyToTemporaryFileReadsRemoteStorage(): void
    {
        $this->configureRemoteDisk('remote/files/report.txt', 'report contents');

        $asset = new Asset([
            'storage'   => 'remote',
            'path'      => 'remote/files/report.txt',
            'file_name' => 'report.txt',
        ]);

        $temporaryFile = $asset->copyToTemporaryFile();

        try {
            $this->assertFileExists($temporaryFile);
            $this->assertStringEndsWith('.txt', $temporaryFile);
            $this->assertSame('report contents', file_get_contents($temporaryFile));
            $this->assertNull($asset->local_path);
        } finally {
            @unlink($temporaryFile);
        }
    }

    public function testWithTemporaryFileCleansUpAfterCallback(): void
    {
        $this->configureRemoteDisk('remote/files/queued.txt', 'queued contents');

        $asset = new Asset([
            'storage'   => 'remote',
            'path'      => 'remote/files/queued.txt',
            'file_name' => 'queued.txt',
        ]);

        $callbackPath = null;
        $result       = $asset->withTemporaryFile(function (string $path) use (&$callbackPath): string {
            $callbackPath = $path;

            $this->assertFileExists($path);

            $contents = file_get_contents($path);
            $this->assertIsString($contents);

            return strtoupper($contents);
        });

        $this->assertSame('QUEUED CONTENTS', $result);
        $this->assertIsString($callbackPath);
        $this->assertFileDoesNotExist($callbackPath);
    }

    public function testCopyToTemporaryFileReadsVariantFromRemoteStorage(): void
    {
        $this->configureRemoteDisk('remote/variants/thumb.png', 'thumb contents');

        $asset = new Asset([
            'id'        => 123,
            'storage'   => 'public',
            'path'      => 'original/report.txt',
            'file_name' => 'report.txt',
            'metadata'  => new AssetMetadata([
                'asset_variants' => [
                    'thumbnail' => new AssetVariant([
                        'name'      => 'thumbnail',
                        'storage'   => 'remote',
                        'path'      => 'remote/variants/thumb.png',
                        'processed' => true,
                    ]),
                ],
            ]),
        ]);

        $temporaryFile = $asset->copyToTemporaryFile('thumbnail');

        try {
            $this->assertFileExists($temporaryFile);
            $this->assertStringEndsWith('.png', $temporaryFile);
            $this->assertSame('thumb contents', file_get_contents($temporaryFile));
        } finally {
            @unlink($temporaryFile);
        }
    }

    /**
     * Test setting entity type with an invalid class name
     */
    public function testSetEntityTypeWithInvalidClassName(): void
    {
        // Arrange
        $invalidClass = 'InvalidClass';

        // Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->asset->setEntityType($invalidClass);
    }

    /**
     * Test setting collection with an AssetCollectionDefinitionInterface instance
     */
    public function testSetCollectionWithInterfaceInstance(): void
    {
        // Arrange
        $this->mockCollectionDefinition                                           = $this->createStub(AssetCollectionDefinitionInterface::class);
        $config                                                                   = config('Asset');
        $config->collectionKeyDefinitions[$this->mockCollectionDefinition::class] = 'mock_definition';

        // Act
        $result = $this->asset->setCollection($this->mockCollectionDefinition);

        // Assert
        $this->assertSame($this->asset, $result);
        $this->assertSame('mock_definition', $this->asset->collection);
    }

    /**
     * Test setting collection with a class name
     */
    public function testSetCollectionWithClassName(): void
    {
        // Arrange
        $collectionClass = DefaultAssetCollection::class;

        Factories::injectMock('config', AssetConfig::class, new TestAssetConfig());

        // Act
        $result = $this->asset->setCollection($collectionClass);

        // Assert
        $this->assertSame($this->asset, $result);
        $this->assertSame('default_collection', $this->asset->collection);
    }

    /**
     * Test setting collection with a string alias name
     */
    public function testSetCollectionWithStringAliasName(): void
    {
        // Arrange
        Factories::injectMock('config', AssetConfig::class, new TestAssetConfig());

        // Act
        $result = $this->asset->setCollection('test_collection');

        // Assert
        $this->assertSame($this->asset, $result);
        $this->assertSame('test_collection', $this->asset->collection, 'The collection should be set to the alias name.');
    }

    /**
     * Test setting collection with an invalid class name
     */
    public function testSetCollectionWithInvalidClassName(): void
    {
        // Arrange
        $invalidClass = 'InvalidCollectionClass';

        // Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->asset->setCollection($invalidClass);
    }

    /**
     * Test getting properties when they are not set
     */
    public function testGetPropertiesWhenNotSet(): void
    {
        // Act
        $properties = $this->asset->metadata;

        // Assert
        $this->assertNotNull($properties);
    }

    /**
     * Test getting properties when they are set as a Properties object
     */
    public function testGetPropertiesWhenSetAsPropertiesObject(): void
    {
        // Create a Properties object with the JSON string
        $properties = new AssetMetadata([
            'key' => 'value',
        ]);

        $setMetadata = $this->getPrivateMethodInvoker($this->asset, 'setMetadata');
        $setMetadata($properties);

        // Act
        $properties = $this->asset->metadata;

        // Assert
        $this->assertInstanceOf(AssetMetadata::class, $properties);
    }

    /**
     * Test getting properties when they are set as a Properties object
     */
    public function testGetPropertiesWhenSetAsObject(): void
    {
        // Arrange
        $propertiesObject = new AssetMetadata();

        $setMetadata = $this->getPrivateMethodInvoker($this->asset, 'setMetadata');
        $setMetadata($propertiesObject);

        // Act
        $properties = $this->asset->metadata;

        // Assert
        $this->assertSame($propertiesObject, $properties);
    }

    /**
     * Test getting extension
     */
    public function testGetExtension(): void
    {
        // Arrange
        $this->mockFile->method('getExtension')
            ->willReturn('jpg');

        $this->asset->file = $this->mockFile;

        // Act
        $extension = $this->asset->extension;

        // Assert
        $this->assertSame('jpg', $extension);
    }

    /**
     * Test getting path dirname when path is set
     */
    public function testGetPathDirnameWhenPathIsSet(): void
    {
        // Arrange
        $path              = '/path/to/file.jpg';
        $this->asset->path = $path;

        // Act
        $dirname = $this->asset->path_dirname;

        // Assert
        $this->assertSame(dirname($path) . DIRECTORY_SEPARATOR, $dirname);
    }

    public function testGetEntityTypeClassNameAccessor(): void
    {
        Factories::injectMock('config', AssetConfig::class, new TestAssetConfig());

        $asset = new Asset([
            'entity_type' => 'test_entity',
        ]);

        $this->assertSame(TestEntity::class, $asset->entity_type_class_name);
    }

    public function testSetMetadataAcceptsNull(): void
    {
        $setMetadata = $this->getPrivateMethodInvoker($this->asset, 'setMetadata');

        $result = $setMetadata(null);

        $this->assertSame($this->asset, $result);
        $this->assertInstanceOf(AssetMetadata::class, $this->asset->metadata);
    }

    public function testMetadataCanBeLoadedFromRawArrayAndRawObjectAttributes(): void
    {
        $assetFromArray = new Asset();
        $assetFromArray->injectRawData([
            'metadata' => [
                'user_custom' => [
                    'source' => 'array',
                ],
            ],
        ]);

        $this->assertSame('array', $assetFromArray->getCustomProperty('source'));

        $metadata = new AssetMetadata([
            'user_custom' => [
                'source' => 'object',
            ],
        ]);

        $assetFromObject = new Asset();
        $assetFromObject->injectRawData([
            'metadata' => $metadata,
        ]);

        $this->assertSame('object', $assetFromObject->getCustomProperty('source'));
    }

    public function testToRawArrayHydratesMissingSizeForNewAsset(): void
    {
        $file = $this->createStub(File::class);
        $file->method('getSize')->willReturn(33);

        $asset = new Asset([
            'storage'   => 'public',
            'path'      => 'raw/file.txt',
            'mime_type' => 'text/plain',
            'file_name' => 'file.txt',
        ]);
        $asset->file = $file;

        $raw = $asset->toRawArray();

        $this->assertSame(33, $raw['size']);
    }

    public function testGetExtensionFallsBackToPath(): void
    {
        $asset = new Asset([
            'path' => 'documents/report.pdf',
        ]);

        $this->assertSame('pdf', $asset->extension);
    }

    public function testGetExtensionThrowsWhenNoFileNamePathOrFileExists(): void
    {
        $this->expectException(AssetInvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid argument provided');

        $this->fail('Expected missing extension data to throw, got: ' . $this->asset->extension);
    }

    public function testGetPathDirnameThrowsWhenPathIsMissing(): void
    {
        $this->expectException(AssetInvalidArgumentException::class);

        $this->fail('Expected missing path dirname to throw, got: ' . $this->asset->path_dirname);
    }

    public function testGetRelativePathThrowsWhenPathIsMissing(): void
    {
        $getRelativePath = $this->getPrivateMethodInvoker($this->asset, 'getRelativePath');

        try {
            $getRelativePath();
        } catch (AssetInvalidArgumentException $exception) {
            $this->assertSame(
                ['File relative path not set.'],
                $exception->errors,
                'Missing asset path should be exposed as the relative path validation error.',
            );

            return;
        }

        $this->fail('Expected missing relative path to throw an asset invalid argument exception.');
    }

    public function testCopyToTemporaryFileThrowsWhenVariantDoesNotExist(): void
    {
        $asset = new Asset([
            'id'       => 123,
            'metadata' => new AssetMetadata(),
        ]);

        $this->expectException(AssetInvalidArgumentException::class);

        $asset->copyToTemporaryFile('missing');
    }

    public function testIsProtectedCollectionFallsBackToAuthorizableCollectionWhenStorageIsMissing(): void
    {
        $config                                                                = new TestAssetConfig();
        $config->collectionKeyDefinitions[ProtectedTestAssetCollection::class] = 'protected_test';

        Factories::injectMock('config', AssetConfig::class, $config);
        Factories::injectMock('config', 'Asset', $config);

        $asset = new Asset([
            'collection' => 'protected_test',
        ]);

        $this->assertTrue($asset->is_protected_collection);
    }

    public function testGetSubjectEntityClassThrowsWhenEntityTypeIsNotRegistered(): void
    {
        $asset = new Asset();
        $asset->injectRawData([
            'entity_type' => 'missing_entity',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Entity class for entity type 'missing_entity' is not registered in asset entity definitions.");

        $asset->getSubjectEntityClass();
    }

    public function testTransferToStorageRejectsBlankTargetStorage(): void
    {
        $asset = new Asset([
            'storage' => 'public',
            'path'    => 'file.txt',
        ]);

        $this->expectException(AssetInvalidArgumentException::class);

        $asset->transferToStorage('   ');
    }

    public function testCopyStoragePathThrowsWhenSourceFileIsMissing(): void
    {
        $sourceDisk = $this->createMock(StorageDiskInterface::class);
        $sourceDisk->method('name')->willReturn('source');
        $sourceDisk->expects($this->once())
            ->method('fileExists')
            ->with('missing.txt')
            ->willReturn(false);

        $targetDisk = $this->createStub(StorageDiskInterface::class);

        $copyStoragePath = $this->getPrivateMethodInvoker($this->asset, 'copyStoragePath');

        $this->expectException(FileException::class);

        $copyStoragePath($sourceDisk, $targetDisk, 'missing.txt');
    }

    public function testCopyStoragePathThrowsWhenTargetFileAlreadyExists(): void
    {
        $sourceDisk = $this->createMock(StorageDiskInterface::class);
        $sourceDisk->method('name')->willReturn('source');
        $sourceDisk->expects($this->once())
            ->method('fileExists')
            ->with('duplicate.txt')
            ->willReturn(true);

        $targetDisk = $this->createMock(StorageDiskInterface::class);
        $targetDisk->method('name')->willReturn('target');
        $targetDisk->expects($this->once())
            ->method('fileExists')
            ->with('duplicate.txt')
            ->willReturn(true);

        $copyStoragePath = $this->getPrivateMethodInvoker($this->asset, 'copyStoragePath');

        $this->expectException(FileException::class);

        $copyStoragePath($sourceDisk, $targetDisk, 'duplicate.txt');
    }

    public function testCopyStoragePathWrapsWriteFailures(): void
    {
        $sourceDisk = $this->createMock(StorageDiskInterface::class);
        $sourceDisk->method('name')->willReturn('source');
        $sourceDisk->expects($this->once())
            ->method('fileExists')
            ->with('write-failure.txt')
            ->willReturn(true);
        $sourceDisk->expects($this->once())
            ->method('readStream')
            ->with('write-failure.txt')
            ->willReturn($this->streamFromString('contents'));

        $targetDisk = $this->createMock(StorageDiskInterface::class);
        $targetDisk->method('name')->willReturn('target');
        $targetDisk->method('visibility')->willReturn(AssetVisibility::PUBLIC);
        $targetDisk->expects($this->once())
            ->method('fileExists')
            ->with('write-failure.txt')
            ->willReturn(false);
        $targetDisk->expects($this->once())
            ->method('writeStream')
            ->willThrowException(new RuntimeException('adapter failed'));

        $copyStoragePath = $this->getPrivateMethodInvoker($this->asset, 'copyStoragePath');

        $this->expectException(FileException::class);

        $copyStoragePath($sourceDisk, $targetDisk, 'write-failure.txt');
    }

    public function testRestoreVariantStoragesRestoresExistingVariantsAndSkipsMissingVariants(): void
    {
        $metadata = new AssetMetadata();
        $metadata->assetVariant->addAssetVariant(new AssetVariant([
            'name'    => 'existing',
            'storage' => 'target',
            'path'    => 'variants/existing.txt',
        ]));

        $asset = new Asset([
            'metadata' => $metadata,
        ]);

        $restoreVariantStorages = $this->getPrivateMethodInvoker($asset, 'restoreVariantStorages');
        $restoreVariantStorages([
            'existing' => 'source',
            'missing'  => 'source',
        ]);

        $variant = $asset->metadata->assetVariant->getAssetVariant('existing');

        $this->assertInstanceOf(AssetVariant::class, $variant);
        $this->assertSame('source', $variant->storage);
    }

    public function testDeleteStoragePathsLogsAndContinuesWhenDeleteFails(): void
    {
        $disk = $this->createMock(StorageDiskInterface::class);
        $disk->expects($this->once())
            ->method('delete')
            ->with('stale-copy.txt')
            ->willThrowException(new RuntimeException('delete failed'));

        $deleteStoragePaths = $this->getPrivateMethodInvoker($this->asset, 'deleteStoragePaths');
        $deleteStoragePaths([
            [
                'disk' => $disk,
                'path' => 'stale-copy.txt',
            ],
        ]);

        $this->addToAssertionCount(1);
    }

    public function testPersistStorageTransferThrowsWhenModelSaveFails(): void
    {
        Factories::injectMock('models', AssetModel::class, new FailingSaveAssetModel());

        $asset = new Asset([
            'id'       => 123,
            'storage'  => 'target',
            'path'     => 'assets/file.txt',
            'metadata' => new AssetMetadata(),
        ]);

        $persistStorageTransfer = $this->getPrivateMethodInvoker($asset, 'persistStorageTransfer');

        try {
            $persistStorageTransfer();
        } catch (AssetException $exception) {
            $this->assertSame(
                ['storage' => 'Unable to update test asset storage.'],
                $exception->errors,
                'Failed storage transfer persistence should expose model validation errors.',
            );

            return;
        } finally {
            Factories::reset('models');
        }

        $this->fail('Expected a database asset exception when the storage transfer cannot be persisted.');
    }

    public function testTransferToStorageRestoresVariantStorageAndDeletesCopiedTargetsWhenTransferFails(): void
    {
        $targetDisk = $this->createMock(StorageDiskInterface::class);
        $targetDisk->method('name')->willReturn('target');
        $targetDisk->method('visibility')->willReturn(AssetVisibility::PUBLIC);
        $targetDisk->method('fileExists')->willReturn(false);
        $targetDisk->expects($this->once())
            ->method('writeStream')
            ->with('variants/copied.txt');
        $targetDisk->expects($this->once())
            ->method('delete')
            ->with('variants/copied.txt')
            ->willThrowException(new RuntimeException('delete failed'));

        $sourceDisk = $this->createMock(StorageDiskInterface::class);
        $sourceDisk->method('name')->willReturn('source');
        $sourceDisk->method('fileExists')->willReturnCallback(static fn (string $path): bool => $path === 'variants/copied.txt');
        $sourceDisk->expects($this->once())
            ->method('readStream')
            ->with('variants/copied.txt')
            ->willReturn($this->streamFromString('variant'));

        $this->configureStorageDisks([
            'target' => $targetDisk,
            'source' => $sourceDisk,
        ]);

        $metadata = new AssetMetadata();
        $metadata->assetVariant->addAssetVariant(new AssetVariant([
            'name'      => 'copied',
            'storage'   => 'source',
            'path'      => 'variants/copied.txt',
            'processed' => true,
        ]));
        $metadata->assetVariant->addAssetVariant(new AssetVariant([
            'name'      => 'missing',
            'storage'   => 'source',
            'path'      => 'variants/missing.txt',
            'processed' => true,
        ]));

        $asset = new Asset([
            'id'       => 123,
            'storage'  => 'target',
            'path'     => 'original.txt',
            'metadata' => $metadata,
        ]);

        try {
            $asset->transferToStorage('target');
        } catch (FileException $exception) {
            $copied = $asset->metadata->assetVariant->getAssetVariant('copied');

            $this->assertInstanceOf(AssetVariant::class, $copied);
            $this->assertSame('source', $copied->storage);
            $this->assertNotSame('', $exception->getMessage());

            return;
        }

        $this->fail('Expected transfer failure for missing processed variant.');
    }

    public function testDownloadDelegatesToAssetAccessService(): void
    {
        $response = $this->createStub(DownloadResponse::class);
        $service  = $this->createMock(AssetAccessServiceInterface::class);
        $service->expects($this->once())
            ->method('handleAssetRequest')
            ->with(123, 'thumbnail')
            ->willReturn($response);

        Services::injectMock('assetAccessService', $service);

        $asset = new Asset([
            'id' => 123,
        ]);

        $this->assertSame($response, $asset->download('thumbnail'));
    }

    public function testJsonSerializeIncludesVariantData(): void
    {
        $config                                   = new TestAssetConfig();
        $config->storages['public']['public_url'] = 'https://cdn.example.test/assets';

        Factories::injectMock('config', AssetConfig::class, $config);
        Factories::injectMock('config', 'Asset', $config);

        $metadata = new AssetMetadata();
        $metadata->assetVariant->addAssetVariant(new AssetVariant([
            'name'      => 'preview',
            'storage'   => 'public',
            'path'      => 'variants/preview.txt',
            'size'      => 1024,
            'processed' => true,
        ]));

        $asset = new Asset([
            'id'         => 123,
            'entity_id'  => 456,
            'collection' => 'default_collection',
            'storage'    => 'public',
            'path'       => 'original.txt',
            'name'       => 'Original',
            'file_name'  => 'original.txt',
            'mime_type'  => 'text/plain',
            'size'       => 2048,
            'order'      => 7,
            'metadata'   => $metadata,
        ]);

        $serialized = $asset->jsonSerialize();

        $this->assertArrayHasKey('preview', $serialized['variants']);
        $this->assertSame('preview', $serialized['variants']['preview']['name']);
        $this->assertSame(1024, $serialized['variants']['preview']['size']);
        $this->assertSame('1 KB', $serialized['variants']['preview']['size_human_readable']);
        $this->assertTrue($serialized['variants']['preview']['processed']);
    }

    /**
     * Test create method with data
     */
    public function testCreateWithData(): void
    {
        // Arrange
        $data = [
            'name'      => 'Test Asset',
            'file_name' => 'test.jpg',
        ];

        // Act
        $asset = Asset::create($data);

        /** @phpstan-ignore-next-line Assert */
        $this->assertInstanceOf(Asset::class, $asset);
        $this->assertSame('Test Asset', $asset->name);
        $this->assertSame('test.jpg', $asset->file_name);
    }

    /**
     * Test create method with null data
     */
    public function testCreateWithNullData(): void
    {
        // Act
        $asset = Asset::create();

        /** @phpstan-ignore-next-line Assert */
        $this->assertInstanceOf(Asset::class, $asset);
    }

    public function testCreateThrowsWhenValidatedModelReturnTypeChanges(): void
    {
        Factories::injectMock('models', AssetModel::class, new InvalidMagicReturnTypeAssetModel());

        try {
            Asset::create();
        } catch (InvalidArgumentException $exception) {
            $this->assertSame(
                'Asset model return type must be a subclass of Asset.',
                $exception->getMessage(),
                'Asset::create should reject a model return type that is not an Asset subclass.',
            );

            return;
        } finally {
            Factories::reset('models');
        }

        $this->fail('Expected Asset::create to reject an invalid model return type.');
    }
}
