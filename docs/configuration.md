# Configuration

CodeIgniter Asset Connect provides several configuration options to customize its behavior. This page explains how to configure the library to suit your needs.

## Configuration File

The configuration for Asset Connect is managed through the `Config\Asset.php` file. If this file doesn't exist in your application's config directory, you can create it:

```php
<?php

namespace Config;

use Maniaba\FileConnect\Config\Asset as BaseAsset;

class Asset extends BaseAsset
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
use Maniaba\FileConnect\AssetCollection\CustomAssetCollection;

public string $defaultCollection = CustomAssetCollection::class;
```

The class must implement the `AssetCollectionDefinitionInterface`.

### Default Path Generator

The path generator determines how file paths are generated for stored assets:

```php
use Maniaba\FileConnect\PathGenerator\CustomPathGenerator;

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

```php
<?php

namespace App\AssetCollections;

use CodeIgniter\Entity\Entity;
use Maniaba\FileConnect\Asset\Asset;
use Maniaba\FileConnect\AssetCollection\FileVariants;
use Maniaba\FileConnect\Enums\AssetExtension;
use Maniaba\FileConnect\Enums\AssetMimeType;
use Maniaba\FileConnect\Interfaces\Asset\AssetCollectionDefinitionInterface;
use Maniaba\FileConnect\Interfaces\Asset\AssetCollectionSetterInterface;
use Maniaba\FileConnect\Interfaces\Asset\FileVariantInterface;
use Maniaba\FileConnect\PathGenerator\CustomPathGenerator;

class ProfilePicturesCollection implements AssetCollectionDefinitionInterface, FileVariantInterface
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

    public function variants(FileVariants $variants, Asset $asset): void
    {
        // Define file variants (e.g., thumbnails)
    }
}
```

## Creating Custom Path Generators

Path generators determine how file paths are generated for stored assets. You can create custom path generators by implementing the `PathGeneratorInterface`:

```php
<?php

namespace App\PathGenerators;

use Maniaba\FileConnect\Asset\Asset;
use Maniaba\FileConnect\PathGenerator\PathGeneratorInterface;

class CustomPathGenerator implements PathGeneratorInterface
{
    public function getPath(Asset $asset): string
    {
        // Generate a custom path for the asset
        return 'custom/' . $asset->getCollection() . '/' . $asset->id . '/' . $asset->getFileName();
    }
}
```
