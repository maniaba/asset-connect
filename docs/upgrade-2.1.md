# Upgrade from 2.0.0 to 2.1.0

AssetConnect 2.1.0 changes how custom storage setup methods are resolved.

> **AssetConnect 3.0 note:** The driver-based fallback described on this page was removed in 3.0.0. Current versions resolve storage setup methods only from the configured storage disk name.

In 2.0.0, setup methods were resolved from the storage `driver` value:

```php
public array $storages = [
    'remote' => [
        'driver' => 'custom_remote',
    ],
];
```

This called:

```php
protected function setupStorageCustomRemote(array $storage): array
{
    // ...
}
```

In 2.1.0, setup methods are resolved from the configured storage disk name first:

```php
protected function setupStorageRemote(array $storage): array
{
    // ...
}
```

## Compatibility in 2.1.x

The 2.1.x release line is backward compatible with 2.0.0 storage setup methods.

In 2.1.x, if `setupStorageRemote()` does not exist, AssetConnect falls back to the legacy driver-based method name, such as `setupStorageCustomRemote()`.

That means existing 2.0.0 applications can upgrade to 2.1.0 without immediately renaming storage setup methods.

## Recommended Migration

Rename custom setup methods from driver-based names to storage-name-based names when convenient.

Before:

```php
public array $storages = [
    'remote' => [
        'driver'     => 'custom_remote',
        'public_url' => 'https://cdn.example.com/assets',
        'visibility' => AssetVisibility::PROTECTED,
    ],
];

protected function setupStorageCustomRemote(array $storage): array
{
    return [
        'adapter' => new SomeOfficialFlysystemAdapter(...),
    ];
}
```

After:

```php
public array $storages = [
    'remote' => [
        'driver'     => 'custom_remote',
        'public_url' => 'https://cdn.example.com/assets',
        'visibility' => AssetVisibility::PROTECTED,
    ],
];

protected function setupStorageRemote(array $storage): array
{
    return [
        'adapter' => new SomeOfficialFlysystemAdapter(...),
    ];
}
```

## Multiple Storages Using One Driver

The new resolution model lets each configured storage disk have its own setup logic even when they share the same driver:

```php
public array $storages = [
    'images' => [
        'driver' => 'custom_remote',
    ],
    'documents' => [
        'driver' => 'custom_remote',
    ],
];

protected function setupStorageImages(array $storage): array
{
    return [
        'adapter' => new ImagesAdapter(...),
    ];
}

protected function setupStorageDocuments(array $storage): array
{
    return [
        'adapter' => new DocumentsAdapter(...),
    ];
}
```

In 2.0.0, both disks would have resolved to `setupStorageCustomRemote()`. In 2.1.0, `images` resolves to `setupStorageImages()` and `documents` resolves to `setupStorageDocuments()`.

## No Data Migration Required

No database migration or asset path migration is required for this upgrade.

Run the package migrations only if your application has not already applied the 2.0.0 migrations:

```bash
php spark migrate --namespace=Maniaba\\AssetConnect
```

## Verification

After upgrading, run your normal test suite and static analysis:

```bash
composer test
composer analyze
```

If your application uses custom storage setup methods, verify at least one upload or read operation for each configured custom storage disk.
