# Advanced Usage

This page covers advanced usage scenarios for CodeIgniter Asset Connect.

## Custom Asset Collections

While the basic usage of Asset Connect is sufficient for many applications, you may need more control over how assets are organized and processed. Custom asset collections provide this flexibility.

### Creating a Custom Asset Collection

To create a custom asset collection, create a class that extends `DefaultAssetCollection` or implements the `AssetCollectionDefinitionInterface`:

```php
class ProductImagesCollection implements AssetCollectionDefinitionInterface, AssetVariantsInterface
{
    public function definition(AssetCollectionSetterInterface $definition): void
    {
        $definition
            // Allow specific file extensions using the AssetExtension enum
            ->allowedExtensions(
                AssetExtension::JPG,
                AssetExtension::PNG,
                AssetExtension::WEBP
            )
            // Alternatively, you can use the spread operator with AssetExtension::images()
            // to allow all image extensions at once:
            // ->allowedExtensions(...AssetExtension::images())
            // Allow specific MIME types using the AssetMimeType enum
            ->allowedMimeTypes(
                AssetMimeType::IMAGE_JPEG,
                AssetMimeType::IMAGE_PNG,
                AssetMimeType::IMAGE_WEBP
            )
            // Set maximum file size (in bytes)
            ->setMaxFileSize(10 * 1024 * 1024) // 10MB
            // Set a custom path generator for this collection
            ->setPathGenerator(new CustomPathGenerator());
    }

    public function checkAuthorization(array|Entity $entity, Asset $asset): bool
    {
        // Check if the user is authorized to access this asset
        // For example, check if the user owns the asset
        return true;
    }

    public function variants(CreateAssetVariantsInterface $variants, Asset $asset): void
    {
        // Define file variants for this asset collection
        // For example, create a thumbnail variant
        if ($asset->isImage()) {
            // Create a thumbnail variant
            // This is just a placeholder - in a real application, you would
            // use an image manipulation library to create the thumbnail
            $variants->assetVariant('thumbnail', static function (AssetVariant $variant, Asset $asset): void {
                $variant->writeFile('thumbnail data');
            });
        }
    }
}
```

### Using Custom Collections in Entities

Once you've created a custom collection, you can use it in your entity's `setupAssetConnect` method:

```php
class Product extends Entity
{
    use UseAssetConnectTrait;

    public function setupAssetConnect(SetupAssetCollection $setup): void
    {
        // Set the default collection definition
        // Note: Only one default collection can be set; additional calls will override previous ones
        $setup->setDefaultCollectionDefinition(ProductImagesCollection::class);

    }
}
```

## Custom Path Generators

Path generators determine how file paths are generated for stored assets. Custom path generators give you control over the directory structure of your stored files.

### Creating a Custom Path Generator

To create a custom path generator, implement the `PathGeneratorInterface`:

```php
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
class Asset extends BaseAsset
{
    public string $defaultPathGenerator = YearMonthPathGenerator::class;
}
```

Or in a specific collection:

```php
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

Asset variants allow you to create different versions of the same asset, such as thumbnails or resized images. Asset Connect provides a flexible way to define and process variants through the `AssetVariantsInterface`.

### Defining Asset Variants

You define asset variants in your collection class by implementing the `AssetVariantsInterface` and its `variants` method:

```php
public function variants(CreateAssetVariantsInterface $variants, Asset $asset): void
{
    $variants->onQueue = true; // Process variants on a queue for better performance

    // Create a thumbnail variant for images
    if ($asset->isImage()) {
        $variants->assetVariant('thumbnail', static function (AssetVariant $variant, Asset $asset): void {
            // Use CodeIgniter's image manipulation service
            $imageService = \Config\Services::image();
            $imageService->withFile($asset->path)
                ->fit(300, 300, 'center')
                ->save($variant->path);
        });

        // Create a medium-sized variant
        $variants->assetVariant('medium', static function (AssetVariant $variant, Asset $asset): void {
            $imageService = \Config\Services::image();
            $imageService->withFile($asset->path)
                ->resize(800, 600, true)
                ->save($variant->path);
        });
    }

    // Create a preview variant for PDF documents
    if ($asset->getMimeType() === 'application/pdf') {
        $variants->assetVariant('preview', static function (AssetVariant $variant, Asset $asset): void {
            // Use a PDF library to create a preview image of the first page
            // This is just a placeholder - in a real application, you would
            // use a PDF library like Imagick or a third-party service
            $variant->writeFile('PDF preview data');
        });
    }
}
```

### Queue Processing

Setting `$variants->onQueue = true` tells Asset Connect to process the variants asynchronously using a queue job. This is especially useful for large files or complex processing operations that might take a significant amount of time. The queue job will be processed in the background, allowing your application to continue responding to user requests.

To use queue processing, you need to have a queue system set up in your CodeIgniter application. Asset Connect uses CodeIgniter's Queue service, which supports various queue drivers like Database, Redis, and more.

### Accessing Variants

Once variants are created, they are stored with the asset and can be accessed through the asset's properties:

```php
$asset = $product->getFirstAsset(ImagesCollection::class);

// Get the URL to a variant
$thumbnailUrl = $asset->properties->fileVariant->getAssetVariant('thumbnail')->getUrl();
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
