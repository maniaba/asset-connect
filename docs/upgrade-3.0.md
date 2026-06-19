# Upgrade from 2.1.0 to 3.0.0

AssetConnect 3.0.0 removes legacy compatibility paths that were kept during the 2.x storage transition.

## Storage Setup Methods

Storage setup methods are now resolved only from the configured storage disk name.

Before:

```php
public array $storages = [
    'remote' => [
        'driver' => 'aws_s3',
    ],
];

protected function setupStorageAwsS3(array $storage): array
{
    return [
        'adapter' => new AwsS3V3Adapter(...),
    ];
}
```

After:

```php
public array $storages = [
    'remote' => [
        'driver' => 'aws_s3',
    ],
];

protected function setupStorageRemote(array $storage): array
{
    return [
        'adapter' => new AwsS3V3Adapter(...),
    ];
}
```

If multiple disks use the same driver, each disk should have its own setup method based on the disk name, such as `setupStorageImages()` or `setupStorageDocuments()`.

## Pending Cleanup API

`cleanExpiredPendingAssets()` was removed from the pending storage and manager APIs.

Remove calls such as:

```php
PendingAssetManager::make()->cleanExpiredPendingAssets();
```

Default pending storage deletes a known pending ID when it is consumed or when that known ID is fetched and found expired. For unconsumed expired pending files, use storage lifecycle rules or keep an application-side index of pending IDs that your application can delete explicitly.

## Verification

After upgrading, run:

```bash
composer test
composer analyze
```
