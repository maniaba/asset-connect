# Advanced Usage

This page covers advanced usage scenarios for CodeIgniter Asset Connect.

## Custom Asset Collections

While the basic usage of Asset Connect is sufficient for many applications, you may need more control over how assets are organized and processed. Custom asset collections provide this flexibility.

### Creating a Custom Asset Collection

To create a custom asset collection, create a class that extends `DefaultAssetCollection` or implements the `AssetCollectionDefinitionInterface`:

```php
<?php

namespace App\AssetCollections;

use CodeIgniter\Entity\Entity;
use Maniaba\FileConnect\Asset\Asset;
use Maniaba\FileConnect\AssetCollection\FileVariants;
use Maniaba\FileConnect\Interfaces\Asset\AssetCollectionDefinitionInterface;
use Maniaba\FileConnect\Interfaces\Asset\AssetCollectionSetterInterface;
use Maniaba\FileConnect\Interfaces\Asset\FileVariantInterface;
use Maniaba\FileConnect\PathGenerator\CustomPathGenerator;

class ProductImagesCollection implements AssetCollectionDefinitionInterface, FileVariantInterface
{
    public function definition(AssetCollectionSetterInterface $definition): void
    {
        $definition->allowedMimeTypes('image/jpeg', 'image/png', 'image/webp')
            ->setMaxFileSize(10 * 1024 * 1024) // 10MB
            ->setPathGenerator(new CustomPathGenerator());
    }

    public function checkAuthorization(array|Entity $entity, Asset $asset): bool
    {
        // Check if the user is authorized to access this asset
        // For example, check if the user owns the asset
        return true;
    }

    public function variants(FileVariants $variants, Asset $asset): void
    {
        // Define file variants for this asset collection
        // For example, create a thumbnail variant
        if ($asset->isImage()) {
            // Create a thumbnail variant
            // This is just a placeholder - in a real application, you would
            // use an image manipulation library to create the thumbnail
            $variants->writeFile('thumbnail', 'thumbnail data');
        }
    }
}
```

### Using Custom Collections in Entities

Once you've created a custom collection, you can use it in your entity's `setupAssetConnect` method:

```php
<?php

namespace App\Entities;

use App\AssetCollections\ProductImagesCollection;
use CodeIgniter\Entity\Entity;
use Maniaba\FileConnect\Traits\UseAssetConnectTrait;
use Maniaba\FileConnect\Interfaces\AssetCollection\SetupAssetCollection;

class Product extends Entity
{
    use UseAssetConnectTrait;

    public function setupAssetConnect(SetupAssetCollection $setup): void
    {
        // Register your custom collection classes
        $setup->setDefaultCollectionDefinition(ProductImagesCollection::class);

        // You can also register default collections by name
        // This will use the DefaultAssetCollection class
        $setup->setDefaultCollectionDefinition(DocumentsCollection::class);
    }
}
```

## Custom Path Generators

Path generators determine how file paths are generated for stored assets. Custom path generators give you control over the directory structure of your stored files.

### Creating a Custom Path Generator

To create a custom path generator, implement the `PathGeneratorInterface`:

```php
<?php

namespace App\PathGenerators;

use Maniaba\FileConnect\Asset\Asset;
use Maniaba\FileConnect\PathGenerator\PathGeneratorInterface;

class YearMonthPathGenerator implements PathGeneratorInterface
{
    public function getPath(Asset $asset): string
    {
        $date = date('Y/m');
        $collection = $asset->getCollection();
        $entityType = $asset->entity_type;
        $entityId = $asset->entity_id;

        return "uploads/{$collection}/{$date}/{$entityType}/{$entityId}/{$asset->id}-{$asset->file_name}";
    }
}
```

### Using Custom Path Generators

You can use custom path generators in your configuration or in specific asset collections:

```php
<?php

namespace Config;

use App\PathGenerators\YearMonthPathGenerator;
use Maniaba\FileConnect\Config\Asset as BaseAsset;

class Asset extends BaseAsset
{
    public string $defaultPathGenerator = YearMonthPathGenerator::class;
}
```

Or in a specific collection:

```php
<?php

namespace App\AssetCollections;

use App\PathGenerators\YearMonthPathGenerator;
use Maniaba\FileConnect\AssetCollection\DefaultAssetCollection;

class ProductImagesCollection extends DefaultAssetCollection
{
    public static function name(): string
    {
        return 'product_images';
    }

    public static function pathGenerator(): string
    {
        return YearMonthPathGenerator::class;
    }
}
```

## Working with Asset Variants

Asset variants allow you to create different versions of the same asset, such as thumbnails or resized images.

### Creating Asset Variants

You can create variants of an asset using the `createVariant` method:

```php
$asset = $product->getFirstAsset(ImagesCollection::class);

// Create a thumbnail variant
$thumbnail = $asset->createVariant('thumbnail', function ($file) {
    // Resize the image to 200x200
    $image = \Config\Services::image()
        ->withFile($file)
        ->resize(200, 200, true)
        ->save($file);

    return $file;
});

// Get the URL to the thumbnail
$thumbnailUrl = $thumbnail->getUrl();
```

### Retrieving Asset Variants

You can retrieve variants of an asset using the `getVariant` method:

```php
$asset = $product->getFirstAsset(ImagesCollection::class);

// Get the thumbnail variant
$thumbnail = $asset->getVariant('thumbnail');

// Get the URL to the thumbnail
$thumbnailUrl = $thumbnail->getUrl();
```

## Events

Asset Connect fires several events that you can listen for in your application:

- `asset.created` - Fired when an asset is created
- `asset.updated` - Fired when an asset is updated
- `asset.deleted` - Fired when an asset is deleted
- `variant.created` - Fired when a variant is created

### Listening for Events

You can listen for these events in your application's `app/Config/Events.php` file:

```php
<?php

namespace Config;

use CodeIgniter\Events\Events;
use Maniaba\FileConnect\Events\AssetCreated;

// ...

Events::on('asset.created', function (AssetCreated $event) {
    $asset = $event->asset;

    // Do something with the asset
    log_message('info', 'Asset created: ' . $asset->id);
});
```

## Integration with Other Libraries

Asset Connect can be integrated with other CodeIgniter libraries to extend its functionality.

### Integration with CodeIgniter Image Manipulation

You can use CodeIgniter's Image Manipulation library to process images before or after they are stored:

```php
$asset = $product->addAsset('/path/to/image.jpg')
    ->withProcessor(function ($file) {
        // Process the image
        $image = \Config\Services::image()
            ->withFile($file)
            ->resize(800, 600, true)
            ->save($file);

        return $file;
    })
    ->toAssetCollection();
```

### Integration with CodeIgniter Cache

You can use CodeIgniter's Cache library to cache asset URLs or other frequently accessed data:

```php
$cache = \Config\Services::cache();

// Get the URL to an asset, caching it for 1 hour
$url = $cache->remember('asset_' . $asset->id . '_url', 3600, function () use ($asset) {
    return $asset->getUrl();
});
```
