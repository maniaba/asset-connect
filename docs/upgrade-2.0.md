# Upgrade to 2.0.0

AssetConnect 2.0.0 stores file locations as:

- `storage`: configured storage disk name, for example `public` or `protected`
- `path`: relative path inside that storage disk

AssetConnect 1.0.1 stored absolute filesystem paths in `assets.path`. The upgrade command migrates those rows file by file and copies each file into the configured 2.0.0 storage disk.

## Before Running

Back up both the database and the legacy files before running the migration.

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

## Dry Run

Start with a dry run. This prints the file-by-file plan without copying files or updating rows:

```bash
php spark asset-connect:migrate-paths \
    --storage public \
    --from-root=/old/app/public \
    --source-root=/current/app/public \
    --dry-run
```

Use:

- `--from-root`: the old path prefix currently stored in `assets.path`
- `--source-root`: where those files are located now
- `--storage`: the 2.0.0 storage disk to copy files into

If the old absolute paths still exist on disk, `--source-root` can be omitted:

```bash
php spark asset-connect:migrate-paths \
    --storage public \
    --from-root=/old/app/public \
    --dry-run
```

## Execute Migration

After reviewing the dry run, remove `--dry-run`:

```bash
php spark asset-connect:migrate-paths \
    --storage public \
    --from-root=/old/app/public \
    --source-root=/current/app/public
```

The command prints one line per asset:

```text
[1/250] asset #15 MIGRATED public:assets/2026/06/file.jpg - File copied and database row updated.
```

For each migrated asset the command:

1. Resolves a storage-relative path from `assets.path`.
2. Copies the file into the target storage disk.
3. Updates the row to `storage = <disk>` and `path = <relative path>`.

## Protected Files

Run the command for protected legacy files using the protected disk and the old protected root:

```bash
php spark asset-connect:migrate-paths \
    --storage protected \
    --from-root=/old/app/writable \
    --source-root=/current/app/writable
```

If `--storage` is omitted, AssetConnect tries to resolve the disk from the asset collection configuration and falls back to the configured default public disk.

## Idempotency

The command is safe to re-run:

- Rows that already use `storage` plus a relative `path` are ignored.
- If the target storage file already exists, the database row is updated without copying again.
- Use `--overwrite` only when the target file should be replaced from the source file.

## Optional Cleanup

By default, legacy source files are kept. To delete each source file after a successful copy and database update:

```bash
php spark asset-connect:migrate-paths \
    --storage public \
    --from-root=/old/app/public \
    --source-root=/current/app/public \
    --delete-source
```

Use this only after a verified backup.

## Large Tables

Limit one run:

```bash
php spark asset-connect:migrate-paths --storage public --from-root=/old/app/public --limit=500
```

Change query batch size:

```bash
php spark asset-connect:migrate-paths --storage public --from-root=/old/app/public --batch-size=50
```

## Verify

After migration, asset rows should have a non-empty `storage` value and `path` should no longer be an absolute filesystem path.

For local public storage, make sure the public URL points to the configured storage root. For example:

```bash
ln -s ../writable/asset-connect/public public/assets/storage
```
