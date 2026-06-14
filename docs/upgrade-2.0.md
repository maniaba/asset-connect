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

Legacy `assets.path` values should already be storage-relative paths. For rows created by older AssetConnect versions, the migration command can also derive the storage-relative path from `metadata.storage_info.storage_base_directory_path` when the absolute `assets.path` is under that legacy base directory.

When the target Flysystem disk does not already contain the file, the command copies the readable legacy filesystem path into the selected storage disk before updating the database row. Arbitrary absolute filesystem paths are still rejected; the command only converts them when the legacy metadata proves which base directory should be removed.

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
2. Validates that `assets.path` is storage-relative, or derives it from supported legacy storage metadata.
3. Verifies that the file exists on the target storage disk, or copies it from the readable legacy absolute path.
4. Updates the row to `storage = <disk>` and `path = <relative path>`.
5. Removes the legacy `metadata.storage_info` property from rows whose storage disk and relative path are already normalized.

## Protected Files

Run the command for protected files using the protected disk:

```bash
php spark asset-connect:migrate-paths --storage protected
```

## Idempotency

The command is safe to re-run:

- Rows that already have a non-empty `storage` value are ignored by path migration, but still have obsolete `metadata.storage_info` cleaned when their path is already storage-relative.
- Rows with arbitrary absolute filesystem paths are rejected instead of being split into root and relative path.
- Rows with older `metadata.storage_info.storage_base_directory_path` metadata can be converted when the absolute path is inside that base directory.

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

For local storage, make sure each public storage URL points to its configured storage root:

```bash
php spark asset-connect:storage-link
```
