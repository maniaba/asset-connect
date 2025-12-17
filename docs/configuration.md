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

### Entity Type Definitions

**Required Configuration**

You **must** define entity types and their unique identifiers for all entities that will use Asset Connect. This is a required configuration for the library to function properly:

```php
public array $entityKeyDefinitions = [
    Product::class => 'product',
    User::class => 'user',
    BlogPost::class => 'blog_post',
];
```

Each entity class is mapped to a unique string identifier. This identifier is stored in the database and is **essential** for Asset Connect to:

- Identify which type of entity the asset belongs to
- Associate assets with the correct entities
- Query assets by entity type
- Maintain data integrity across your application

**Example:**

```php
use App\Entities\Product;
use App\Entities\User;

public array $entityKeyDefinitions = [
    Product::class => 'product',
    User::class => 'user',
];
```

The entity class must extend `CodeIgniter\Entity\Entity`.

**Important:** Every entity that uses the `UseAssetConnectTrait` must be registered in this array. Failure to do so will prevent the library from functioning correctly.

### Collection Key Definitions

**Required Configuration**

You **must** define collection key definitions for all asset collections used in your application. These unique identifiers are required for Asset Connect to work properly:

```php
public array $collectionKeyDefinitions = [
    ImagesCollection::class => 'images',
    DocumentsCollection::class => 'documents',
    VideosCollection::class => 'videos',
];
```

Each collection class is mapped to a unique string identifier that is stored in the database. This is **essential** for:

- **Asset Collection Management**: Identifying which collection an asset belongs to
- **Database Integrity**: Maintaining consistent data relationships
- **Query Operations**: Enabling the library to filter and retrieve assets by collection type
- **Refactoring Safety**: Allowing you to change class names without breaking existing data

**Example:**

```php
use App\AssetCollections\ProfilePicturesCollection;
use App\AssetCollections\ProductImagesCollection;
use App\AssetCollections\DocumentsCollection;

public array $collectionKeyDefinitions = [
    ProfilePicturesCollection::class => 'profile_pictures',
    ProductImagesCollection::class => 'product_images',
    DocumentsCollection::class => 'documents',
];
```

The collection class must implement the `AssetCollectionDefinitionInterface`.

**Important:** Every asset collection class you create must be registered in this array. Without this registration, Asset Connect will not be able to process assets for that collection.

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
