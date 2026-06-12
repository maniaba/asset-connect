<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Maniaba\AssetConnect\Config\Asset as AssetConfig;
use Maniaba\AssetConnect\Upgrade\LegacyAssetPathMigrationOptions;
use Maniaba\AssetConnect\Upgrade\LegacyAssetPathMigrationProgress;
use Maniaba\AssetConnect\Upgrade\LegacyAssetPathMigrator;
use Override;
use Throwable;

final class MigrateLegacyAssetPaths extends BaseCommand
{
    protected $group       = 'AssetConnect';
    protected $name        = 'asset-connect:migrate-paths';
    protected $description = 'Migrates AssetConnect 1.0.1 absolute asset paths to 2.0.0 storage-relative paths.';
    protected $usage       = 'asset-connect:migrate-paths [options]';
    protected $options     = [
        '--storage'       => 'Target storage disk name. If omitted, the collection/default storage configuration is used.',
        '--from-root'     => 'Old root path that is currently stored as the prefix in assets.path.',
        '--source-root'   => 'Actual filesystem root where legacy files are now located. Defaults to --from-root.',
        '--dry-run'       => 'Print what would be migrated without copying files or updating the database.',
        '--delete-source' => 'Delete the legacy source file after a successful copy and database update.',
        '--overwrite'     => 'Overwrite files that already exist in the target storage disk.',
        '--limit'         => 'Maximum number of candidate rows to process.',
        '--batch-size'    => 'Number of candidate rows to load per query. Defaults to 100.',
    ];

    /**
     * @param array<int|string, string|null> $params
     */
    #[Override]
    public function run(array $params): int
    {
        $options = new LegacyAssetPathMigrationOptions(
            storage: $this->stringOption($params, 'storage'),
            fromRoot: $this->stringOption($params, 'from-root'),
            sourceRoot: $this->stringOption($params, 'source-root'),
            dryRun: $this->boolOption($params, 'dry-run'),
            deleteSource: $this->boolOption($params, 'delete-source'),
            overwrite: $this->boolOption($params, 'overwrite'),
            limit: $this->intOption($params, 'limit'),
            batchSize: $this->intOption($params, 'batch-size') ?? 100,
        );

        /** @var AssetConfig $config */
        $config = config('Asset');

        CLI::write('AssetConnect legacy path migration', 'yellow');

        if ($options->dryRun) {
            CLI::write('Dry run: no files will be copied and no database rows will be updated.', 'light_gray');
        }

        try {
            $migrator = new LegacyAssetPathMigrator($config);
            $summary  = $migrator->migrate($options, $this->writeProgress(...));
        } catch (Throwable $exception) {
            $this->showError($exception);

            return EXIT_ERROR;
        }

        if ($summary->total === 0) {
            CLI::write('No legacy asset paths found.', 'green');

            return EXIT_SUCCESS;
        }

        CLI::newLine();
        CLI::table([
            ['Candidates', (string) $summary->total],
            ['Migrated', (string) $summary->migrated],
            ['Dry run', (string) $summary->dryRun],
            ['Skipped', (string) $summary->skipped],
            ['Failed', (string) $summary->failed],
        ]);

        return $summary->failed > 0 ? EXIT_ERROR : EXIT_SUCCESS;
    }

    private function writeProgress(LegacyAssetPathMigrationProgress $progress): void
    {
        $color = match ($progress->status) {
            LegacyAssetPathMigrationProgress::STATUS_MIGRATED => 'green',
            LegacyAssetPathMigrationProgress::STATUS_DRY_RUN  => 'yellow',
            LegacyAssetPathMigrationProgress::STATUS_SKIPPED  => 'light_gray',
            LegacyAssetPathMigrationProgress::STATUS_FAILED   => 'red',
            default                                           => 'white',
        };

        $line = sprintf(
            '[%d/%d] asset #%d %s',
            $progress->current,
            $progress->total,
            $progress->assetId,
            strtoupper($progress->status),
        );

        if ($progress->storage !== '' || $progress->relativePath !== '') {
            $line .= sprintf(' %s:%s', $progress->storage, $progress->relativePath);
        }

        $line .= ' - ' . $progress->message;

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

    /**
     * @param array<int|string, string|null> $params
     */
    private function intOption(array $params, string $name): ?int
    {
        $value = $params[$name] ?? CLI::getOption($name);

        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return max(0, (int) $value);
    }
}
