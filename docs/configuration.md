# Configuration

CodeIgniter Asset Connect provides several configuration options to customize its behavior. This page explains how to configure the library to suit your needs.

## Configuration File

The configuration for Asset Connect is managed through the `Config\Asset.php` file. If this file doesn't exist in your application's config directory, you can create it:

```php
class Asset extends BaseConfig
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

### Default Path Generator

The path generator determines how file paths are generated for stored assets:

```php
public string $defaultPathGenerator = CustomPathGenerator::class;
```

The class must implement the `PathGeneratorInterface`.

### Table Names

You can customize the name of the database table used by Asset Connect:

```php
public array $tables = [
    'assets' => 'custom_assets_table',
];
```

This is useful if you need to rename the default table for security reasons, to prevent conflicts, or to comply with your organization's naming conventions.

### Queue Name

You can specify the name of the queue that will be used for processing asset manipulations and garbage collection:

```php
public string $queueName = 'custom_asset_queue';
```

The queue serves an important role in the file deletion process. When you delete assets from an entity using the `deleteAssets()` method, the records are immediately marked with soft delete in the database, but the actual files are not immediately removed from storage. Instead, a queue job is scheduled to clean up these files later. This approach prevents performance issues when deleting large numbers of files and ensures that file system operations don't slow down your application's response time.

## Creating Custom Asset Collections

Asset collections allow you to organize your assets into logical groups. You can create custom collections by implementing the `AssetCollectionDefinitionInterface`:

Here's a simple example of a collection that only allows image files and limits the size to 5 MB:

```php
class SingleImageCollection implements AssetCollectionDefinitionInterface, AssetVariantsInterface
{
    public function definition(AssetCollectionSetterInterface $definition): void
    {
        $definition->singleFileCollection()
            ->setMaxFileSize(5 * 1024 * 1024) // 5 MB
            ->allowedExtensions(...AssetExtension::images());
    }

    public function checkAuthorization(array|Entity $entity, Asset $asset): bool
    {
        return true;
    }

    public function variants(CreateAssetVariantsInterface $variants, Asset $asset): void
    {
        // No variants needed
    }
}
```

For more complex collections, you can customize the configuration further:

```php
class ProfilePicturesCollection implements AssetCollectionDefinitionInterface, AssetVariantsInterface
{
    public function definition(AssetCollectionSetterInterface $definition): void
    {
        // Allow specific file extensions using the AssetExtension enum
        $definition->allowedExtensions(
            AssetExtension::JPG,
            AssetExtension::PNG,
            AssetExtension::GIF,
            // You can also use string values
            'webp'
        )
        // Alternatively, you can use the spread operator with AssetExtension::images()
        // to allow all image extensions at once
        // ->allowedExtensions(...AssetExtension::images())
        // Allow specific MIME types using the AssetMimeType enum
        ->allowedMimeTypes(
            AssetMimeType::IMAGE_JPEG,
            AssetMimeType::IMAGE_PNG,
            AssetMimeType::IMAGE_GIF,
            // You can also use string values
            'image/webp'
        )
        // Set maximum file size (in bytes)
        ->setMaxFileSize(5 * 1024 * 1024) // 5MB

        // Make this a single-file collection (only one file allowed)
        // This is equivalent to calling onlyKeepLatest(1)
        ->singleFileCollection()

        // OR set a maximum number of files to keep (deletes oldest when exceeded)
        // ->onlyKeepLatest(10) // Keep only the 10 most recent files

        // Set a custom path generator for this collection
        ->setPathGenerator(new CustomPathGenerator());
    }

    public function checkAuthorization(array|Entity $entity, Asset $asset): bool
    {
        // Check if the user is authorized to access this asset
        return true;
    }

    public function variants(CreateAssetVariantsInterface $variants, Asset $asset): void
    {
        // Define file variants (e.g., thumbnails)
    }
}
```

## Custom Path Generators

The path generator determines how file paths are generated for stored assets. You can configure a custom path generator in your configuration:

```php
public string $defaultPathGenerator = CustomPathGenerator::class;
```

The class must implement the `PathGeneratorInterface`.

For detailed information about creating and customizing path generators, see the [Custom Path Generators](custom-path-generators.md) documentation.
