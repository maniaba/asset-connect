# Upgrade to 2.0.0

AssetConnect 2.0.0 stores file locations as:

- `storage`: configured Flysystem disk name, for example `public` or `protected`
- `path`: relative path inside that storage disk

Older AssetConnect versions used `storage_base_directory_path` as part of the filesystem path model. That value is no longer stored because physical roots now live in `Config\Asset::$storages`.

## Before Running

Back up the database and files before running the migration.

Run the AssetConnect migrations first so the `storage` column exists:

```bash
php spark migrate --namespace=Maniaba\\AssetConnect
```

Configure the target storage disks in `Config\Asset`:

```php
public array $storages = [
    'public' => [
        'driver'     => 'local',
        'root'       => WRITEPATH . 'asset-connect/public',
        'public_url' => 'assets/storage',
        'visibility' => 'public',
    ],
    'protected' => [
        'driver'     => 'local',
        'root'       => WRITEPATH . 'asset-connect/protected',
        'visibility' => 'protected',
    ],
];
```

Legacy `assets.path` values must already be storage-relative paths. The migration command does not split absolute filesystem paths into base directory and relative path.

## Dry Run

Start with a dry run. This prints the database update plan without changing rows:

```bash
php spark asset-connect:migrate-paths \
    --storage public \
    --dry-run
```

Use:

- `--storage`: the 2.0.0 storage disk to assign to matching rows
- `--dry-run`: print the plan without updating rows

If `--storage` is omitted, AssetConnect tries to resolve the disk from the asset collection configuration and falls back to the configured default public disk.

## Execute Migration

After reviewing the dry run, remove `--dry-run`:

```bash
php spark asset-connect:migrate-paths --storage public
```

The command prints one line per asset:

```text
[1/250] asset #15 MIGRATED public:assets/2026/06/file.jpg - Database row updated.
```

For each migrated asset the command:

1. Resolves the target storage disk.
2. Validates that `assets.path` is storage-relative.
3. Verifies that the file exists on the target storage disk.
4. Updates the row to `storage = <disk>` and keeps `path = <relative path>`.

## Protected Files

Run the command for protected files using the protected disk:

```bash
php spark asset-connect:migrate-paths --storage protected
```

## Idempotency

The command is safe to re-run:

- Rows that already have a non-empty `storage` value are ignored.
- Rows with absolute filesystem paths are rejected instead of being split into root and relative path.

## Large Tables

Limit one run:

```bash
php spark asset-connect:migrate-paths --storage public --limit=500
```

Change query batch size:

```bash
php spark asset-connect:migrate-paths --storage public --batch-size=50
```

## Verify

After migration, asset rows should have a non-empty `storage` value and `path` should remain a storage-relative path.

For local public storage, make sure the public URL points to the configured storage root. For example:

```bash
ln -s ../writable/asset-connect/public public/assets/storage
```
