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
        'public_url' => 'assets/protected',
        'visibility' => 'protected',
    ],
];
```

Public collections use `$defaultPublicStorage` unless the collection selects a storage disk explicitly. Protected collections use `$defaultProtectedStorage`.

## Link Local Storage

For local storage disks that define `public_url`, expose the configured root through your web server. The recommended setup is to create links from the public folder to each storage root:

```bash
php spark asset-connect:storage-link
```

This creates links such as:

```text
public/assets/storage   -> writable/asset-connect/public
public/assets/protected -> writable/asset-connect/protected
```

Limit the command to one disk when needed:

```bash
php spark asset-connect:storage-link --storage protected
```

The link path must match each disk's `public_url`. If you use a web server alias instead of a filesystem link, point it to the same storage root.

## Protected Storage

Protected storage is a separate disk selected by protected collections, but URLs are still generated directly from the disk's `public_url`. If you expose the default protected disk with `asset-connect:storage-link`, files are served by the web server without the AssetConnect controller.

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
        // Used by applications that still call the authorization service directly.
        // Direct web-server URLs are not checked by this method.
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
