<?php

declare(strict_types=1);

namespace Tests\Config;

use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;
use Maniaba\AssetConnect\Config\Asset;
use Maniaba\AssetConnect\Enums\AssetVisibility;
use Maniaba\AssetConnect\Storage\StorageManager;

/**
 * @internal
 */
final class AssetConfigTest extends CIUnitTestCase
{
    public function testStorageNameSetupMethodsCanTransformStorageConfig(): void
    {
        $config = new class () extends Asset {
            public array $receivedStorageConfig = [];

            /**
             * {@inheritDoc}
             */
            public array $storages = [
                'remote' => [
                    'driver'     => 'testing_remote',
                    'root'       => 'before-setup',
                    'visibility' => AssetVisibility::PROTECTED,
                ],
            ];

            /**
             * @param array<string, mixed> $storage
             *
             * @return array{driver: string, root: string}
             */
            protected function setupStorageRemote(array $storage): array
            {
                $this->receivedStorageConfig = $storage;

                return [
                    'driver' => 'local',
                    'root'   => HOMEPATH . 'build/asset-connect/remote',
                ];
            }
        };

        $this->assertSame('testing_remote', $config->receivedStorageConfig['driver']);
        $this->assertArrayHasKey('remote', $config->storages);
        $this->assertSame('local', $config->storages['remote']['driver']);
        $this->assertSame(AssetVisibility::PROTECTED, $config->storages['remote']['visibility']);

        $disk = (new StorageManager($config))->disk('remote');

        $this->assertSame('remote', $disk->name());
        $this->assertSame(AssetVisibility::PROTECTED, $disk->visibility());
    }

    public function testStorageNameSetupMethodsCanCustomizeStoragesUsingSameDriver(): void
    {
        $config = new class () extends Asset {
            /**
             * {@inheritDoc}
             */
            public array $storages = [
                'images' => [
                    'driver' => 'custom_remote',
                ],
                'documents' => [
                    'driver' => 'custom_remote',
                ],
            ];

            /**
             * @param array<string, mixed> $storage
             *
             * @return array{driver: string, root: string, visibility: AssetVisibility}
             */
            protected function setupStorageImages(array $storage): array
            {
                return [
                    'driver'     => 'local',
                    'root'       => HOMEPATH . 'build/asset-connect/images',
                    'visibility' => AssetVisibility::PUBLIC,
                ];
            }

            /**
             * @param array<string, mixed> $storage
             *
             * @return array{driver: string, root: string, visibility: AssetVisibility}
             */
            protected function setupStorageDocuments(array $storage): array
            {
                return [
                    'driver'     => 'local',
                    'root'       => HOMEPATH . 'build/asset-connect/documents',
                    'visibility' => AssetVisibility::PROTECTED,
                ];
            }
        };

        $this->assertSame(HOMEPATH . 'build/asset-connect/images', $config->storages['images']['root']);
        $this->assertSame(AssetVisibility::PUBLIC, $config->storages['images']['visibility']);
        $this->assertSame(HOMEPATH . 'build/asset-connect/documents', $config->storages['documents']['root']);
        $this->assertSame(AssetVisibility::PROTECTED, $config->storages['documents']['visibility']);
    }

    public function testStorageNameSetupMethodsTakePriorityOverDriverSetupMethods(): void
    {
        $config = new class () extends Asset {
            public string $usedSetupMethod = '';

            /**
             * {@inheritDoc}
             */
            public array $storages = [
                's3' => [
                    'driver' => 'aws_s3',
                ],
            ];

            /**
             * @param array<string, mixed> $storage
             *
             * @return array{driver: string, root: string}
             */
            protected function setupStorageS3(array $storage): array
            {
                $this->usedSetupMethod = 'storage-name';

                return [
                    'driver' => 'local',
                    'root'   => HOMEPATH . 'build/asset-connect/by-name',
                ];
            }

            /**
             * @param array<string, mixed> $storage
             *
             * @return array{driver: string, root: string}
             */
            protected function setupStorageAwsS3(array $storage): array
            {
                $this->usedSetupMethod = 'driver';

                return [
                    'driver' => 'local',
                    'root'   => HOMEPATH . 'build/asset-connect/by-driver',
                ];
            }
        };

        $this->assertSame('storage-name', $config->usedSetupMethod);
        $this->assertSame(HOMEPATH . 'build/asset-connect/by-name', $config->storages['s3']['root']);
    }

    public function testStorageDriverSetupMethodsAreIgnoredWithoutMatchingStorageNameSetup(): void
    {
        $config = new class () extends Asset {
            public array $receivedStorageConfig = [];

            /**
             * {@inheritDoc}
             */
            public array $storages = [
                'legacy' => [
                    'driver'     => 'aws_s3',
                    'root'       => 'before-setup',
                    'visibility' => 'public',
                ],
            ];

            /**
             * @param array<string, mixed> $storage
             *
             * @return array{driver: string, root: string, visibility: string}
             */
            protected function setupStorageAwsS3(array $storage): array
            {
                $this->receivedStorageConfig = $storage;

                return [
                    'driver'     => 'local',
                    'root'       => HOMEPATH . 'build/asset-connect/aws-s3',
                    'visibility' => 'public',
                ];
            }
        };

        $this->assertArrayHasKey('legacy', $config->storages);
        $this->assertSame([], $config->receivedStorageConfig);
        $this->assertSame('aws_s3', $config->storages['legacy']['driver']);
        $this->assertSame('before-setup', $config->storages['legacy']['root']);
        $this->assertSame('public', $config->storages['legacy']['visibility']);
    }

    public function testStorageSetupMethodsMustReturnArrays(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Storage setup method 'setupStorageBroken' must return an array.");

        new class () extends Asset {
            /**
             * {@inheritDoc}
             */
            public array $storages = [
                'broken' => [
                    'driver' => 'broken',
                ],
            ];

            /**
             * @param array<string, mixed> $storage
             */
            protected function setupStorageBroken(array $storage): string
            {
                return 'remote';
            }
        };
    }
}
