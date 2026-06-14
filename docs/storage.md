# Storage

AssetConnect stores files through named storage disks. The database stores only:

- `storage`: the configured disk name, for example `public`, `protected`, or `s3`
- `path`: the relative path inside that disk, for example `assets/2026-06-11/101530.123456/photo.jpg`

Physical root paths are not stored in the database. This keeps assets portable when the application directory changes, because only the storage configuration needs to point to the new root.

## Flysystem

AssetConnect uses Flysystem 3 for storage operations. Local disks are supported out of the box through the configured `local` driver. Any official Flysystem adapter can be provided through configuration as an `adapter`. You can also provide a prebuilt `FilesystemOperator` or a custom `StorageDiskInterface` implementation when you need full control.

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

## Official Flysystem Adapters

AssetConnect keeps only the local Flysystem dependency in its own `composer.json`. Applications install the extra adapter package they need, define the disk in `$storages`, and add a `setupStorage{Driver}()` method that creates the adapter for that driver.

See the [official Flysystem adapter documentation](https://flysystem.thephpleague.com/docs/) for connection-specific options.

| Adapter | Package |
|---|---|
| Local | Included with `league/flysystem` |
| FTP | `league/flysystem-ftp:^3.0` |
| SFTP | `league/flysystem-sftp-v3:^3.0` |
| Memory | `league/flysystem-memory:^3.0` |
| AWS S3 | `league/flysystem-aws-s3-v3:^3.0` |
| AsyncAws S3 | `league/flysystem-async-aws-s3:^3.0` |
| Google Cloud Storage | `league/flysystem-google-cloud-storage:^3.0` |
| Azure Blob Storage | `league/flysystem-azure-blob-storage:^3.0` |
| MongoDB GridFS | `league/flysystem-gridfs:^3.0` |
| WebDAV | `league/flysystem-webdav:^3.0` |

Generic configuration shape:

```php
use League\Flysystem\FilesystemAdapter;
use Maniaba\AssetConnect\Enums\AssetVisibility;

public array $storages = [
    'remote' => [
        'driver'     => 'custom_remote',
        'public_url' => 'https://cdn.example.com/assets',
        'visibility' => AssetVisibility::PROTECTED,
    ],
];

/**
 * @param array<string, mixed> $storage
 *
 * @return array{adapter: FilesystemAdapter}
 */
protected function setupStorageCustomRemote(array $storage): array
{
    /** @var FilesystemAdapter $adapter */
    $adapter = new SomeOfficialFlysystemAdapter(...);

    return ['adapter' => $adapter];
}
```

The setup method name is generated from the `driver` value. For example, `aws_s3` calls `setupStorageAwsS3()`, `async_aws_s3` calls `setupStorageAsyncAwsS3()`, and `webdav` calls `setupStorageWebdav()`. The returned array is merged over the original disk configuration.

Example AWS S3 disk:

```bash
composer require league/flysystem-aws-s3-v3:^3.0 aws/aws-sdk-php
```

```php
use Aws\S3\S3Client;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use Maniaba\AssetConnect\Enums\AssetVisibility;

public array $storages = [
    's3' => [
        'driver'     => 'aws_s3',
        'bucket'     => 'my-bucket',
        'prefix'     => 'asset-connect',
        'public_url' => 'https://cdn.example.com',
        'visibility' => AssetVisibility::PUBLIC,
    ],
];

/**
 * @param array<string, mixed> $storage
 *
 * @return array{adapter: AwsS3V3Adapter}
 */
protected function setupStorageAwsS3(array $storage): array
{
    $client = new S3Client([
        'version' => 'latest',
        'region'  => env('AWS_DEFAULT_REGION', 'eu-central-1'),
    ]);

    return [
        'adapter' => new AwsS3V3Adapter(
            $client,
            (string) $storage['bucket'],
            (string) ($storage['prefix'] ?? ''),
        ),
    ];
}
```

If the application already builds a Flysystem instance elsewhere, pass it with `filesystem` instead:

```php
use League\Flysystem\Filesystem;
use Maniaba\AssetConnect\Enums\AssetVisibility;

public array $storages = [
    'remote' => [
        'driver'     => 'remote_filesystem',
        'visibility' => AssetVisibility::PROTECTED,
    ],
];

/**
 * @param array<string, mixed> $storage
 *
 * @return array{filesystem: Filesystem}
 */
protected function setupStorageRemoteFilesystem(array $storage): array
{
    $adapter = new SomeOfficialFlysystemAdapter(...);

    return ['filesystem' => new Filesystem($adapter)];
}
```

For non-local adapters, `local_path` is `null`. Use storage streams, `writeFile()`, or a processor that reads and writes through the disk instead of passing paths to APIs that require local files.

## Link Local Storage

For local public storage disks that define `public_url`, expose the configured root through your web server. The recommended setup is to create links from the public folder to each public storage root:

```bash
php spark asset-connect:storage-link
```

This creates links such as:

```text
public/assets/storage -> writable/asset-connect/public
```

Limit the command to one disk when needed:

```bash
php spark asset-connect:storage-link --storage public
```

The link path must match the disk's `public_url`. If you use a web server alias instead of a filesystem link, point it to the same storage root.

## Protected Storage

Protected storage is a separate disk selected by protected collections. Protected asset URLs are generated through AssetConnect routes, so requests go through the AssetConnect controller and `AuthorizableAssetCollectionDefinitionInterface::checkAuthorization()`. Do not expose protected storage roots with `public_url`, `asset-connect:storage-link`, or a web-server alias.

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

For older rows that still contain absolute filesystem paths, the command can convert them only when `metadata.storage_info.storage_base_directory_path` identifies the legacy base directory. If the selected Flysystem disk does not already contain the file, the command copies the legacy source file into that disk before updating the row. After a row has a normalized storage disk and relative path, the command removes the obsolete `metadata.storage_info` property. See [Upgrade to 2.0.0](upgrade-2.0.md) for the full migration workflow.
