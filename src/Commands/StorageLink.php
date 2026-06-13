<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Maniaba\AssetConnect\Config\Asset as AssetConfig;
use Maniaba\AssetConnect\Storage\StorageLinker;
use Maniaba\AssetConnect\Storage\StorageLinkResult;
use Override;

final class StorageLink extends BaseCommand
{
    protected $group       = 'AssetConnect';
    protected $name        = 'asset-connect:storage-link';
    protected $description = 'Creates public links for local AssetConnect storage disks.';
    protected $usage       = 'asset-connect:storage-link [options]';
    protected $options     = [
        '--storage' => 'Only link one configured storage disk.',
        '--force'   => 'Replace an existing symlink target when possible.',
        '--dry-run' => 'Print links that would be created without changing the filesystem.',
    ];

    /**
     * @param array<int|string, string|null> $params
     */
    #[Override]
    public function run(array $params): int
    {
        /** @var AssetConfig $config */
        $config = config('Asset');

        $storage = $this->stringOption($params, 'storage');
        $force   = $this->boolOption($params, 'force');
        $dryRun  = $this->boolOption($params, 'dry-run');

        CLI::write('AssetConnect storage links', 'yellow');

        if ($dryRun) {
            CLI::write('Dry run: no filesystem links will be created.', 'light_gray');
        }

        $results = (new StorageLinker($config))->link($storage, $force, $dryRun);
        $failed  = 0;

        foreach ($results as $result) {
            if ($result->status === StorageLinkResult::STATUS_FAILED) {
                $failed++;
            }

            $this->writeResult($result);
        }

        return $failed > 0 ? EXIT_ERROR : EXIT_SUCCESS;
    }

    private function writeResult(StorageLinkResult $result): void
    {
        $color = match ($result->status) {
            StorageLinkResult::STATUS_LINKED   => 'green',
            StorageLinkResult::STATUS_EXISTING => 'light_gray',
            StorageLinkResult::STATUS_SKIPPED  => 'yellow',
            StorageLinkResult::STATUS_FAILED   => 'red',
            default                            => 'white',
        };

        $line = sprintf('[%s] %s - %s', strtoupper($result->status), $result->storage, $result->message);

        if ($result->source !== '' || $result->target !== '') {
            $line .= sprintf(' (%s -> %s)', $result->target, $result->source);
        }

        CLI::write($line, $color);
    }

    /**
     * @param array<int|string, string|null> $params
     */
    private function stringOption(array $params, string $name): ?string
    {
        $value = $params[$name] ?? CLI::getOption($name);

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    /**
     * @param array<int|string, string|null> $params
     */
    private function boolOption(array $params, string $name): bool
    {
        return array_key_exists($name, $params) || CLI::getOption($name) !== null;
    }
}
