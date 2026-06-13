# Storage

AssetConnect stores files through named storage disks. The database stores only:

- `storage`: the configured disk name, for example `public`, `protected`, or `s3`
- `path`: the relative path inside that disk, for example `assets/2026-06-11/101530.123456/photo.jpg`

Physical root paths are not stored in the database. This keeps assets portable when the application directory changes, because only the storage configuration needs to point to the new root.

## Flysystem

AssetConnect uses Flysystem 3 for storage operations. Local disks are supported out of the box through the configured `local` driver. Other Flysystem adapters can be provided through configuration as a `FilesystemOperator` or a custom `StorageDiskInterface` implementation.

Core operations use storage-relative paths:

```php
$disk->writeStream('assets/photo.jpg', $stream);
$disk->readStream('assets/photo.jpg');
$disk->delete('assets/photo.jpg');
$disk->publicUrl('assets/photo.jpg');
```

## Default Configuration

```php
public string $defaultPublicStorage = 'public';
public string $defaultProtectedStorage = 'protected';

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

Public collections use `$defaultPublicStorage` unless the collection selects a storage disk explicitly. Protected collections use `$defaultProtectedStorage`.

## Public Local Storage

For local public storage, expose the configured root through your web server. A common setup is a symlink from the public folder to the storage root:

```bash
ln -s ../writable/asset-connect/public public/assets/storage
```

The symlink target must match the `root` configured for the `public` disk, and the public URL path must match `public_url`.

## Protected Storage

Protected storage should not be web-accessible. Assets in collections implementing `AuthorizableAssetCollectionDefinitionInterface` are served through the AssetConnect controller after authorization.

```php
final class PrivateDocumentsCollection implements AuthorizableAssetCollectionDefinitionInterface
{
    public function definition(AssetCollectionSetterInterface $definition): void
    {
        $definition
            ->setStorage('protected')
            ->allowedExtensions('pdf');
    }

    public function checkAuthorization(Asset $asset): bool
    {
        return service('auth')->user()?->id === $asset->entity_id;
    }
}
```

## Selecting Storage Per Collection

Use `setStorage()` in a collection definition when a collection must use a specific disk:

```php
final class ProductImagesCollection implements AssetCollectionDefinitionInterface
{
    public function definition(AssetCollectionSetterInterface $definition): void
    {
        $definition
            ->setStorage('public')
            ->allowedExtensions('jpg', 'png', 'webp');
    }
}
```

If `setStorage()` is not used, AssetConnect selects the disk from the collection visibility.

## Local Paths For Processing

`Asset::path` and `AssetVariant::path` are storage-relative paths. Do not pass them directly to APIs that require local filesystem paths.

For local disks, use `local_path`:

```php
$source = $asset->local_path;
$target = $variant->local_path;

if ($source === null || $target === null) {
    throw new RuntimeException('This variant processor requires a local storage disk.');
}

service('image')
    ->withFile($source)
    ->fit(300, 300, 'center')
    ->save($target);
```

For non-local disks, use storage streams or write the result through `$variant->writeFile()`.

## Migration Note

Use the `asset-connect:migrate-paths` command when upgrading legacy rows that have no `storage` disk value and either storage-relative paths or supported legacy storage metadata:

```bash
php spark asset-connect:migrate-paths --storage public --dry-run
```

For older rows that still contain absolute filesystem paths, the command can convert them only when `metadata.storage_info.storage_base_directory_path` identifies the legacy base directory. If the selected Flysystem disk does not already contain the file, the command copies the legacy source file into that disk before updating the row. See [Upgrade to 2.0.0](upgrade-2.0.md) for the full migration workflow.
