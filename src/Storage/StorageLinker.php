<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Storage;

use Maniaba\AssetConnect\Config\Asset as AssetConfig;
use Maniaba\AssetConnect\Enums\AssetVisibility;

final readonly class StorageLinker
{
    public function __construct(
        private AssetConfig $config,
        private string $publicRoot = FCPATH,
    ) {
    }

    /**
     * @return list<StorageLinkResult>
     */
    public function link(?string $storage = null, bool $force = false, bool $dryRun = false): array
    {
        $results = [];

        foreach ($this->config->storages as $name => $storageConfig) {
            if ($storage !== null && $name !== $storage) {
                continue;
            }

            if (! is_array($storageConfig)) {
                $results[] = $this->result($name, StorageLinkStatus::SKIPPED, '', '', 'Storage config is not an array.');

                continue;
            }

            $results[] = $this->linkStorage($name, $storageConfig, $force, $dryRun);
        }

        if ($results === [] && $storage !== null) {
            return [$this->result($storage, StorageLinkStatus::FAILED, '', '', 'Storage disk is not configured.')];
        }

        return $results;
    }

    /**
     * @param array<string, mixed> $storageConfig
     */
    private function linkStorage(string $name, array $storageConfig, bool $force, bool $dryRun): StorageLinkResult
    {
        $root = $storageConfig['root'] ?? null;

        if (! is_string($root) || trim($root) === '') {
            return $this->result($name, StorageLinkStatus::SKIPPED, '', '', 'Only local storage disks with a root path can be linked.');
        }

        if ($this->visibility($storageConfig) === AssetVisibility::PROTECTED) {
            return $this->result($name, StorageLinkStatus::SKIPPED, $root, '', 'Protected storage disks are served through AssetConnect routes and should not be publicly linked.');
        }

        $publicUrl = $this->publicUrlPath($storageConfig['public_url'] ?? $storageConfig['url'] ?? null);

        if ($publicUrl === null) {
            return $this->result($name, StorageLinkStatus::SKIPPED, $root, '', 'Storage disk does not define a linkable public_url.');
        }

        $source = rtrim($root, DIRECTORY_SEPARATOR);
        $target = rtrim($this->publicRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $publicUrl);

        if ($dryRun) {
            return $this->result($name, StorageLinkStatus::LINKED, $source, $target, 'Would create storage link.');
        }

        if (! is_dir($source) && ! mkdir($source, 0755, true) && ! is_dir($source)) {
            return $this->result($name, StorageLinkStatus::FAILED, $source, $target, 'Storage root directory could not be created.');
        }

        $existingStatus = $this->existingLinkStatus($source, $target);
        if ($existingStatus !== null) {
            return $this->result($name, StorageLinkStatus::EXISTING, $source, $target, $existingStatus);
        }

        if (file_exists($target) || is_link($target)) {
            if (! $force || ! is_link($target)) {
                return $this->result($name, StorageLinkStatus::FAILED, $source, $target, 'Target path already exists.');
            }

            if (! unlink($target)) {
                return $this->result($name, StorageLinkStatus::FAILED, $source, $target, 'Existing symlink could not be removed.');
            }
        }

        $targetParent = dirname($target);
        if (! is_dir($targetParent) && ! mkdir($targetParent, 0755, true) && ! is_dir($targetParent)) {
            return $this->result($name, StorageLinkStatus::FAILED, $source, $target, 'Target parent directory could not be created.');
        }

        if (! $this->createLink($source, $target)) {
            return $this->result($name, StorageLinkStatus::FAILED, $source, $target, 'Storage link could not be created.');
        }

        return $this->result($name, StorageLinkStatus::LINKED, $source, $target, 'Storage link created.');
    }

    private function publicUrlPath(mixed $publicUrl): ?string
    {
        if (! is_string($publicUrl)) {
            return null;
        }

        $publicUrl = trim($publicUrl);

        if (
            $publicUrl === ''
            || str_starts_with($publicUrl, '//')
            || preg_match('#^[a-z][a-z0-9+.-]*://#i', $publicUrl) === 1
        ) {
            return null;
        }

        return trim(str_replace('\\', '/', $publicUrl), '/');
    }

    /**
     * @param array<string, mixed> $storageConfig
     */
    private function visibility(array $storageConfig): AssetVisibility
    {
        $visibility = $storageConfig['visibility'] ?? AssetVisibility::PUBLIC;

        if ($visibility instanceof AssetVisibility) {
            return $visibility;
        }

        return AssetVisibility::from((string) $visibility);
    }

    private function existingLinkStatus(string $source, string $target): ?string
    {
        if (! file_exists($target) && ! is_link($target)) {
            return null;
        }

        $sourceReal = realpath($source);
        $targetReal = realpath($target);

        if ($sourceReal !== false && $targetReal !== false && $sourceReal === $targetReal) {
            return 'Storage link already exists.';
        }

        return null;
    }

    private function createLink(string $source, string $target): bool
    {
        if (@symlink($source, $target)) {
            return true;
        }

        if (PHP_OS_FAMILY !== 'Windows') {
            return false;
        }

        $command = 'cmd /C mklink /J ' . escapeshellarg($target) . ' ' . escapeshellarg($source);
        $output  = [];
        $code    = 1;

        exec($command, $output, $code);

        return $code === 0;
    }

    private function result(string $storage, StorageLinkStatus $status, string $source, string $target, string $message): StorageLinkResult
    {
        return new StorageLinkResult($storage, $status, $source, $target, $message);
    }
}
