<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Storage;

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Maniaba\AssetConnect\Config\Asset as AssetConfig;
use Maniaba\AssetConnect\Enums\AssetVisibility;
use Maniaba\AssetConnect\Exceptions\InvalidArgumentException;
use Maniaba\AssetConnect\Storage\Interfaces\StorageDiskInterface;

final class StorageManager
{
    /**
     * @var array<string, StorageDiskInterface>
     */
    private array $resolvedDisks = [];

    public function __construct(
        private readonly AssetConfig $config,
    ) {
    }

    public function disk(string $name): StorageDiskInterface
    {
        if (isset($this->resolvedDisks[$name])) {
            return $this->resolvedDisks[$name];
        }

        $storageConfig = $this->config->storages[$name] ?? null;

        if (! is_array($storageConfig)) {
            throw new InvalidArgumentException("Storage disk '{$name}' is not configured.");
        }

        $disk = $this->resolveDisk($name, $storageConfig);

        return $this->resolvedDisks[$name] = $disk;
    }

    public function defaultDiskNameForVisibility(AssetVisibility $visibility): string
    {
        if ($visibility === AssetVisibility::PROTECTED) {
            return $this->config->defaultProtectedStorage;
        }

        return $this->config->defaultPublicStorage;
    }

    public static function make(?AssetConfig $config = null): self
    {
        /** @var AssetConfig $assetConfig */
        $assetConfig = $config ?? config('Asset');

        return new self($assetConfig);
    }

    /**
     * @param array<string, mixed> $storageConfig
     */
    private function resolveDisk(string $name, array $storageConfig): StorageDiskInterface
    {
        $disk = $storageConfig['disk'] ?? null;
        if ($disk instanceof StorageDiskInterface) {
            return $disk;
        }

        $filesystem = $storageConfig['filesystem'] ?? null;
        $root       = null;

        if (! $filesystem instanceof FilesystemOperator) {
            $adapter = $storageConfig['adapter'] ?? null;
            if ($adapter instanceof FilesystemAdapter) {
                $filesystem = new Filesystem(
                    $adapter,
                    $this->filesystemConfig($storageConfig),
                );

                if ($adapter instanceof LocalFilesystemAdapter) {
                    $configuredRoot = $storageConfig['root'] ?? null;
                    $root           = is_string($configuredRoot) && $configuredRoot !== '' ? $configuredRoot : null;
                }
            } else {
                $driver = (string) ($storageConfig['driver'] ?? 'local');

                if ($driver !== 'local') {
                    throw new InvalidArgumentException("Storage disk '{$name}' uses unsupported driver '{$driver}'. Provide a FilesystemAdapter, FilesystemOperator, or StorageDiskInterface instance.");
                }

                $root = $storageConfig['root'] ?? null;
                if (! is_string($root) || $root === '') {
                    throw new InvalidArgumentException("Local storage disk '{$name}' must define a non-empty root path.");
                }

                $filesystem = new Filesystem(
                    $this->localAdapter($root),
                    $this->filesystemConfig($storageConfig),
                );
            }
        }

        return new FlysystemStorageDisk(
            $name,
            $filesystem,
            $this->visibility($storageConfig),
            is_string($root) ? $root : null,
        );
    }

    private function localAdapter(string $root): FilesystemAdapter
    {
        return new LocalFilesystemAdapter($root);
    }

    /**
     * @param array<string, mixed> $storageConfig
     *
     * @return array<string, list<string>|string>
     */
    private function filesystemConfig(array $storageConfig): array
    {
        $publicUrl = $storageConfig['public_url'] ?? $storageConfig['url'] ?? null;

        if (is_string($publicUrl) && $publicUrl !== '') {
            return ['public_url' => $publicUrl];
        }

        if (is_array($publicUrl)) {
            return ['public_url' => array_values(array_filter($publicUrl, is_string(...)))];
        }

        return [];
    }

    /**
     * @param array<string, mixed> $storageConfig
     */
    private function visibility(array $storageConfig): AssetVisibility
    {
        $visibility = $storageConfig['visibility'] ?? AssetVisibility::PUBLIC->value;

        if ($visibility instanceof AssetVisibility) {
            return $visibility;
        }

        return AssetVisibility::from((string) $visibility);
    }
}
