# Configuration

CodeIgniter Asset Connect provides several configuration options to customize its behavior. This page explains how to configure the library to suit your needs.

## Configuration File

The configuration for Asset Connect is managed through the `Config\Asset.php` file. If this file doesn't exist in your application's config directory, you can create it:

```php
use Maniaba\AssetConnect\Config\Asset as BaseAssetConfig;

class Asset extends BaseAssetConfig
{
    // Your custom configuration here
}
```

## Available Configuration Options

### Database Group

You can specify which database group to use for the Asset Connect models:

```php
public ?string $DBGroup = 'default';
```

If set to `null` (default), the library will use the default database group configured in your application.

### Default Asset Collection

You can change the default collection class that will be used when no specific collection is provided:

```php
public string $defaultCollection = CustomAssetCollection::class;
```

The class must implement the `AssetCollectionDefinitionInterface`.

For detailed information about creating and customizing asset collections, see the [Custom Asset Collections](custom-asset-collections.md) documentation.

### Default Path Generator

The path generator determines how file paths are generated for stored assets:

```php
public string $defaultPathGenerator = CustomPathGenerator::class;
```

The class must implement the `PathGeneratorInterface`.

For detailed information about creating and customizing path generators, see the [Custom Path Generators](custom-path-generators.md) documentation.

### Default URL Generator

The URL generator determines how URLs are generated for accessing assets:

```php
public ?string $defaultUrlGenerator = CustomUrlGenerator::class;
```

The class must implement the `UrlGeneratorInterface`. If set to `null`, the default URL generator will be used and routes will not be registered.

For detailed information about URL generators, see the [Custom URL Generator](custom-url-generator.md) documentation.

### Table Names

You can customize the name of the database table used by Asset Connect:

```php
public array $tables = [
    'assets' => 'custom_assets_table',
];
```

This is useful if you need to rename the default table for security reasons, to prevent conflicts, or to comply with your organization's naming conventions.

### Queue Configuration

You can specify the configuration for the queue that will be used for processing asset manipulations and garbage collection:

```php
public array $queue = [
    'name'       => 'asset_connect_queue',
    'jobHandler' => [
        'name'  => 'asset_variants_process',
        'class' => AssetConnectJob::class,
    ],
];
```

The queue serves an important role in the file deletion process. When you delete assets from an entity using the `deleteAssets()` method, the records are immediately marked with soft delete in the database, but the actual files are not immediately removed from storage. Instead, a queue job is scheduled to clean up these files later. This approach prevents performance issues when deleting large numbers of files and ensures that file system operations don't slow down your application's response time.
