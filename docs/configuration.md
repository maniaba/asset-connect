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

Here's a simple example of a collection that only allows image files and limits the size to 5 MB:

```php
<?php

namespace App\AssetCollections;

use CodeIgniter\Entity\Entity;
use Maniaba\FileConnect\Asset\Asset;
use Maniaba\FileConnect\AssetCollection\AssetVariants;
use Maniaba\FileConnect\Enums\AssetExtension;
use Maniaba\FileConnect\Interfaces\Asset\AssetCollectionDefinitionInterface;
use Maniaba\FileConnect\Interfaces\Asset\AssetCollectionSetterInterface;
use Maniaba\FileConnect\Interfaces\Asset\AssetVariantsInterface;

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

    public function variants(AssetVariants $variants, Asset $asset): void
    {
        // No variants needed
    }
}
```

For more complex collections, you can customize the configuration further:

```php
<?php

namespace App\AssetCollections;

use CodeIgniter\Entity\Entity;
use Maniaba\FileConnect\Asset\Asset;
use Maniaba\FileConnect\AssetCollection\AssetVariants;
use Maniaba\FileConnect\Enums\AssetExtension;
use Maniaba\FileConnect\Enums\AssetMimeType;
use Maniaba\FileConnect\Interfaces\Asset\AssetCollectionDefinitionInterface;
use Maniaba\FileConnect\Interfaces\Asset\AssetCollectionSetterInterface;
use Maniaba\FileConnect\Interfaces\Asset\AssetVariantsInterface;
use Maniaba\FileConnect\PathGenerator\CustomPathGenerator;

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

    public function variants(AssetVariants $variants, Asset $asset): void
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

use Maniaba\FileConnect\Enums\AssetVisibility;
use Maniaba\FileConnect\Interfaces\Asset\AssetCollectionGetterInterface;
use Maniaba\FileConnect\PathGenerator\PathGeneratorHelper;
use Maniaba\FileConnect\PathGenerator\PathGeneratorInterface;

class CustomPathGenerator implements PathGeneratorInterface
{
    // Get the path for the given asset, relative to the root storage path.
    // It's important to generate unique paths to prevent file overwrites.
    public function getPath(PathGeneratorHelper $generatorHelper, AssetCollectionGetterInterface $collection): string
    {
        // Check if the collection is protected (non-public) or public
        $isProtected = $collection->getVisibility() === AssetVisibility::PROTECTED;

        // Set the base path based on visibility
        $basePath = $isProtected ? WRITEPATH : realpath(ROOTPATH . 'public') . DIRECTORY_SEPARATOR;

        // Generate a unique path using helper methods
        // This creates a structure like: assets/2023-05-25/123456.789/
        return $basePath . $generatorHelper->getPathString(
            'assets',
            $generatorHelper->getDateTime(),
            $generatorHelper->getUniqueId()
        );
    }

    // Get the path for variants (e.g., thumbnails) of the given asset.
    // This should also generate unique paths.
    public function getPathForVariants(PathGeneratorHelper $generatorHelper, AssetCollectionGetterInterface $collection): string
    {
        // Get the base path from the getPath method
        $basePath = $this->getPath($generatorHelper, $collection);

        // Add a 'variants' subdirectory
        return $basePath . $generatorHelper->getPathString('variants');
    }
}
```

### Understanding PathGeneratorHelper

The `PathGeneratorHelper` class provides several useful methods for generating unique paths:

1. `getUniqueId(bool $moreEntropy = false)`: Generates a unique ID, with an option for more entropy.
   ```php
   // Basic unique ID
   $uniqueId = $generatorHelper->getUniqueId(); // e.g., "1620000000_60a1b2c3"

   // More secure unique ID with higher entropy
   $secureId = $generatorHelper->getUniqueId(true); // SHA-256 hash
   ```

2. `getDateTime()`: Generates a date-time string formatted as a folder name.
   ```php
   $dateTimeFolder = $generatorHelper->getDateTime(); // e.g., "2023-05-25/123456.789/"
   ```

3. `getTime()`: Gets the current time formatted as a string.
   ```php
   $timeString = $generatorHelper->getTime(); // e.g., "123456.789"
   ```

4. `getPathString(string ...$segments)`: Joins path segments with the system's directory separator.
   ```php
   $path = $generatorHelper->getPathString('folder1', 'folder2', 'folder3'); // "folder1/folder2/folder3/"
   ```

### Understanding AssetCollectionGetterInterface

The `AssetCollectionGetterInterface` is passed to the path generator methods and provides information about the asset collection:

1. `getVisibility()`: Returns the visibility of the collection (PUBLIC or PROTECTED).
   ```php
   $visibility = $collection->getVisibility(); // AssetVisibility::PUBLIC or AssetVisibility::PROTECTED
   ```

2. `getMaximumNumberOfItemsInCollection()`: Returns the maximum number of items allowed in the collection.
3. `getMaxFileSize()`: Returns the maximum file size allowed in the collection.
4. `isSingleFileCollection()`: Returns whether the collection is limited to a single file.
5. `getAllowedMimeTypes()`: Returns an array of allowed MIME types.
6. `getAllowedExtensions()`: Returns an array of allowed file extensions.

These methods can be useful when generating paths, especially `getVisibility()` which helps determine whether to store files in a public or protected location.

### Importance of Unique Paths

When implementing custom path generators, it's crucial to ensure that each asset gets a unique path. This prevents files from overwriting each other, especially in high-traffic applications where multiple files might be uploaded simultaneously.

The `PathGeneratorHelper` class provides methods like `getUniqueId()` and `getDateTime()` specifically to help generate unique paths. By combining these with other identifiers (like collection names, entity IDs, etc.), you can create a robust path generation strategy that minimizes the risk of collisions.
