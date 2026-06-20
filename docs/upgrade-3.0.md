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

## Pending Storage and Security

`DefaultPendingStorage` now writes pending files through the configured AssetConnect storage disk system. Pending storage must resolve to a protected disk.

Review these config values after upgrading:

```php
public string $pendingStorage = \Maniaba\AssetConnect\Pending\DefaultPendingStorage::class;
public ?string $pendingStorageDisk = null; // null uses defaultProtectedStorage
public string $pendingStoragePrefix = 'assets_pending';
public ?string $pendingSecurityToken = \Maniaba\AssetConnect\Pending\PendingSecurityToken\SessionPendingSecurityToken::class;
public ?string $pendingSecurityKey = null;
```

If `pendingStorageDisk` is `null`, AssetConnect uses `defaultProtectedStorage`. If you set `pendingStorageDisk`, make sure that disk has protected visibility.

For browser uploads, the default `SessionPendingSecurityToken` binds the pending asset to the current session with an HMAC digest. The client only needs to submit `pending_id` on the final form submit.

For API/JWT uploads, use `OwnerPendingSecurityToken` with `PendingOwnerResolverInterface`:

```php
public ?string $pendingSecurityToken = \Maniaba\AssetConnect\Pending\PendingSecurityToken\OwnerPendingSecurityToken::class;
public \Maniaba\AssetConnect\Pending\Interfaces\PendingOwnerResolverInterface|string|null $pendingOwnerResolver = JwtPendingOwnerResolver::class;
```

The resolver should return a stable authenticated owner value, such as the JWT `sub`, or a stronger value such as `sub:jti` when the pending asset must be tied to a specific token/device.

When confirming a pending upload, prefer passing the pending ID directly:

```php
$user->addAssetFromPending($pendingId)
    ->toAssetCollection(ProfilePhotos::class);
```

Passing the ID consumes the pending entry, deletes the pending file/metadata and token, then stores the final asset from a temporary source file. Passing an already fetched `PendingAsset` object is still supported, but it does not consume the remote pending entry automatically.

## Verification

After upgrading, run:

```bash
composer test
composer analyze
composer inspect
```
