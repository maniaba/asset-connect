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
    public function testStorageDriverSetupMethodsCanTransformStorageConfig(): void
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
            protected function setupStorageTestingRemote(array $storage): array
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

    public function testStorageDriverNamesAreConvertedToSetupMethodNames(): void
    {
        $config = new class () extends Asset {
            public array $receivedStorageConfig = [];

            /**
             * {@inheritDoc}
             */
            public array $storages = [
                's3' => [
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

        $this->assertArrayHasKey('s3', $config->storages);
        $this->assertSame('aws_s3', $config->receivedStorageConfig['driver']);
        $this->assertSame('local', $config->storages['s3']['driver']);
        $this->assertSame('public', $config->storages['s3']['visibility']);
    }

    public function testStorageDriverSetupMethodsMustReturnArrays(): void
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
