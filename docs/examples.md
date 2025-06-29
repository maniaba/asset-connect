# Examples

This page provides practical examples of how to use CodeIgniter Asset Connect in your application.

## Filtering Assets

You can filter assets using the `filterAssets` method on your model:

```php
// Filter assets by size range
$user = model(User::class, false)
    ->filterAssets(fn(AssetModel $model) => $model->filterBySizeRange(1, 10000000000000))
    ->first();
```

### Available Filters

The AssetModel provides various filtering methods that can be categorized into different groups. These filters can be chained together for more complex queries.

#### Basic Metadata Filters

These filters allow you to filter assets based on their basic metadata:

```php
// Find assets with a specific name
$model->filterAssets(function(AssetModel $model) {
    $model->filterByName('profile-picture');
});

// Find assets with a specific file name
$model->filterAssets(function(AssetModel $model) {
    $model->filterByFileName('profile.jpg');
});

// Find assets with a specific MIME type
$model->filterAssets(function(AssetModel $model) {
    $model->filterByMimeType('image/jpeg');
});

// Find assets with a specific size (with comparison operator)
$model->filterAssets(function(AssetModel $model) {
    $model->filterBySize(1024, '>='); // Files >= 1KB
});

// Find assets with a specific path
$model->filterAssets(function(AssetModel $model) {
    $model->filterByPath('/uploads/images/');
});

// Find assets with a specific order
$model->filterAssets(function(AssetModel $model) {
    $model->filterByOrder(1);
});
```

#### Custom Property Filters

These filters allow you to filter assets based on their custom properties:

```php
// Find assets with a specific custom property value
$model->filterAssets(function(AssetModel $model) {
    $model->filterByProperty('title', 'Profile Picture');
});

// Find assets that have a specific property (regardless of value)
$model->filterAssets(function(AssetModel $model) {
    $model->filterByPropertyExists('title');
});

// Find assets where an array property contains a specific value
$model->filterAssets(function(AssetModel $model) {
    $model->filterByPropertyContains('tags', 'profile');
});
```

#### Date Filters

These filters allow you to filter assets based on dates:

```php
// Find assets created after a specific date
$model->filterAssets(function(AssetModel $model) {
    $model->filterByCreatedAt('2023-01-01', '>=');
});

// Find assets updated before a specific date
$model->filterAssets(function(AssetModel $model) {
    $model->filterByUpdatedAt('2023-12-31', '<=');
});

// Find assets created within a date range
$model->filterAssets(function(AssetModel $model) {
    $model->filterByDateRange('2023-01-01', '2023-12-31', 'created_at');
});
```

#### Pattern Matching Filters

These filters allow you to filter assets using pattern matching:

```php
// Find assets with names matching a pattern
$model->filterAssets(function(AssetModel $model) {
    $model->filterByNameLike('profile%'); // Names starting with "profile"
});

// Find assets with file names matching a pattern
$model->filterAssets(function(AssetModel $model) {
    $model->filterByFileNameLike('%.jpg'); // File names ending with ".jpg"
});
```

#### Range Filters

These filters allow you to filter assets within specific ranges:

```php
// Find assets within a size range
$model->filterAssets(function(AssetModel $model) {
    $model->filterBySizeRange(1024, 1024 * 1024); // Between 1KB and 1MB
});
```

#### Collection and Entity Filters

These filters allow you to filter assets by collection or entity type:

```php
// Find assets in a specific collection
$model->filterAssets(function(AssetModel $model) {
    $model->whereCollection(ImagesCollection::class);
});

// Find assets associated with a specific entity type
$model->filterAssets(function(AssetModel $model) {
    $model->whereEntityType(User::class);
});
```

#### Chaining Filters

One of the most powerful features of the filtering system is the ability to chain multiple filters together to create complex queries. Here are some examples:

```php
// Find large JPEG images uploaded recently
$model->filterAssets(function(AssetModel $model) {
    $model->filterByMimeType('image/jpeg')
          ->filterBySizeRange(1000000, PHP_INT_MAX) // Larger than 1MB
          ->filterByCreatedAt(date('Y-m-d', strtotime('-7 days')), '>=');
});

// Find PDF documents with specific properties
$model->filterAssets(function(AssetModel $model) {
    $model->filterByMimeType('application/pdf')
          ->filterByPropertyExists('title')
          ->filterByPropertyExists('author')
          ->filterByFileNameLike('report%');
});

// Find assets in a specific collection with a specific tag
$model->filterAssets(function(AssetModel $model) {
    $model->whereCollection(DocumentsCollection::class)
          ->filterByPropertyContains('tags', 'important')
          ->filterByOrder(1);
});

// Find images that match specific criteria
$model->filterAssets(function(AssetModel $model) {
    $model->whereCollection(ImagesCollection::class)
          ->filterByNameLike('%profile%')
          ->filterBySize(500000, '<=') // Less than 500KB
          ->filterByUpdatedAt(date('Y-m-d'), '='); // Updated today
});
```

These examples demonstrate how you can combine multiple filters to create precise queries that match exactly what you're looking for.

## Working with Collections

You can retrieve assets from a specific collection:

```php
// Get assets from a specific collection
$assets = $user->getAssets(ImagesCollection::class);
```

## Adding Assets

You can add assets to an entity with various options:

```php
// Create a File object from a local file path
$file = new File('./images/placeholder.jpg');

// Create a File object from an uploaded file in a controller
$uploadedFile = $this->request->getFile('profile_image');
if ($uploadedFile->isValid() && !$uploadedFile->hasMoved()) {
    // You can use the uploaded file directly
    $asset = $user->addAsset($uploadedFile)
        ->usingFileName($uploadedFile->getRandomName())
        ->toAssetCollection(ProfilePicturesCollection::class);

    // Or move it to a temporary location first
    $uploadedFile->move(WRITEPATH . 'uploads', $uploadedFile->getRandomName());
    $file = new File(WRITEPATH . 'uploads/' . $uploadedFile->getName());
}

// Add the asset with various options
$asset = $user->addAsset($file)
    ->usingFileName('test-placeholder.jpg') // Set a custom file name
    ->usingName('Test placeholder') // Set a display name
    ->setOrder(1) // Set the order
    ->preservingOriginal() // Keep the original file
    ->sanitizingFileName(static fn (string $filename): string => str_replace(['#', '/', '\\', ' '], '-', $filename)) // Sanitize the file name
    ->withCustomProperties([
        'custom_property_1' => 'value1',
        'custom_property_2' => 'value2',
    ]) // Add custom properties
    ->toAssetCollection(null); // Save to the default collection
```

### Adding to a Specific Collection

```php
// Add to a specific collection
$asset = $user->addAsset($file)
    ->usingFileName('profile.jpg')
    ->toAssetCollection(ProfilePicturesCollection::class);
```

### Adding with a Single Custom Property

```php
// Add with a single custom property
$asset = $user->addAsset($file)
    ->withCustomProperty('title', 'My Profile Picture')
    ->toAssetCollection(null);
```

## Retrieving Assets

There are several ways to retrieve assets:

```php
// Get all assets for an entity
$allAssets = $user->getAssets();

// Get assets from a specific collection
$profilePictures = $user->getAssets(ProfilePicturesCollection::class);

// Get the first asset
$firstAsset = $user->getFirstAsset();

// Get the first asset from a specific collection
$profilePicture = $user->getFirstAsset(ProfilePicturesCollection::class);

// Get the last asset from a specific collection
$lastDocument = $user->getLastAsset(DocumentsCollection::class);
```

## Deleting Assets

You can delete assets from an entity:

```php
// Delete all assets for an entity
$user->deleteAssets();

// Delete assets from a specific collection
$user->deleteAssets(ImagesCollection::class);
```

## Working with Asset Properties

Once you have an asset, you can work with its properties:

```php
// Get an asset
$asset = $user->getFirstAsset();

// Get the absolute path to the asset file
$path = $asset->getAbsolutePath();

// Get the URL to the asset file
$url = $asset->getUrl();

// Get the custom properties of the asset
$properties = $asset->getCustomProperties();

// Get a specific custom property
$title = $asset->getCustomProperty('title');

// Get the file name
$fileName = $asset->getFileName();

// Get the mime type
$mimeType = $asset->getMimeType();

// Get the size in bytes
$size = $asset->getSize();

// Get the human-readable size
$readableSize = $asset->getHumanReadableSize();

// Check if the asset is an image
if ($asset->isImage()) {
    // Do something with the image
}

// Check if the asset is a video
if ($asset->isVideo()) {
    // Do something with the video
}

// Check if the asset is a document
if ($asset->isDocument()) {
    // Do something with the document
}
```

## Combining Multiple Filters

You can combine multiple filters to create complex queries:

```php
// Find users with large profile pictures
$users = model(User::class, false)
    ->filterAssets(function(AssetModel $model) {
        $model->whereCollection(ProfilePicturesCollection::class)
              ->filterBySizeRange(1000000, PHP_INT_MAX) // Larger than 1MB
              ->filterByMimeType('image/jpeg');
    })
    ->findAll();

// Find users with recently updated documents
$users = model(User::class, false)
    ->filterAssets(function(AssetModel $model) {
        $model->whereCollection(DocumentsCollection::class)
              ->filterByUpdatedAt(date('Y-m-d', strtotime('-7 days')), '>=');
    })
    ->findAll();
```

## Advanced Example: Image Gallery

Here's a more complex example showing how to implement an image gallery:

```php
// First, define custom collection classes for more control
namespace App\AssetCollections;

use CodeIgniter\Entity\Entity;
use Maniaba\FileConnect\Asset\Asset;
use Maniaba\FileConnect\AssetCollection\AssetVariants;
use Maniaba\FileConnect\Enums\AssetExtension;
use Maniaba\FileConnect\Enums\AssetMimeType;
use Maniaba\FileConnect\Interfaces\Asset\AssetCollectionDefinitionInterface;
use Maniaba\FileConnect\Interfaces\Asset\AssetCollectionSetterInterface;
use Maniaba\FileConnect\Interfaces\Asset\FileVariantInterface;

class ImagesCollection implements AssetCollectionDefinitionInterface, FileVariantInterface
{
    public function definition(AssetCollectionSetterInterface $definition): void
    {
        $definition
            // Allow specific file extensions using the AssetExtension enum
            ->allowedExtensions(
                AssetExtension::JPG,
                AssetExtension::PNG,
                AssetExtension::GIF,
                AssetExtension::WEBP
            )
            // Alternatively, you can use the spread operator with AssetExtension::images()
            // to allow all image extensions at once:
            // ->allowedExtensions(...AssetExtension::images())
            // Allow specific MIME types using the AssetMimeType enum
            ->allowedMimeTypes(
                AssetMimeType::IMAGE_JPEG,
                AssetMimeType::IMAGE_PNG,
                AssetMimeType::IMAGE_GIF,
                AssetMimeType::IMAGE_WEBP
            )
            ->setMaxFileSize(10 * 1024 * 1024); // 10MB
    }

    public function checkAuthorization(array|Entity $entity, Asset $asset): bool
    {
        return true;
    }

    public function variants(AssetVariants $variants, Asset $asset): void
    {
        // No variants needed as we'll create thumbnails separately
    }
}

class ThumbnailsCollection implements AssetCollectionDefinitionInterface, FileVariantInterface
{
    public function definition(AssetCollectionSetterInterface $definition): void
    {
        $definition
            // Allow specific file extensions using the AssetExtension enum
            ->allowedExtensions(
                AssetExtension::JPG,
                AssetExtension::PNG,
                AssetExtension::GIF,
                AssetExtension::WEBP
            )
            // Allow specific MIME types using the AssetMimeType enum
            ->allowedMimeTypes(
                AssetMimeType::IMAGE_JPEG,
                AssetMimeType::IMAGE_PNG,
                AssetMimeType::IMAGE_GIF,
                AssetMimeType::IMAGE_WEBP
            )
            ->setMaxFileSize(2 * 1024 * 1024); // 2MB
    }

    public function checkAuthorization(array|Entity $entity, Asset $asset): bool
    {
        return true;
    }

    public function variants(AssetVariants $variants, Asset $asset): void
    {
        // No variants needed for thumbnails
    }
}

// In your entity class
namespace App\Entities;

use App\AssetCollections\ImagesCollection;
use App\AssetCollections\ThumbnailsCollection;
use CodeIgniter\Entity\Entity;
use Maniaba\FileConnect\Traits\UseAssetConnectTrait;
use Maniaba\FileConnect\Interfaces\AssetCollection\SetupAssetCollection;

class Gallery extends Entity
{
    use UseAssetConnectTrait;

    public function setupAssetConnect(SetupAssetCollection $setup): void
    {
        // Set the default collection definition
        // Note: Only one default collection can be set; additional calls will override previous ones
        $setup->setDefaultCollectionDefinition(ImagesCollection::class);

    }
}

// In your controller
public function uploadImage()
{
    $file = $this->request->getFile('image');

    if ($file->isValid() && !$file->hasMoved()) {
        $gallery = model(Gallery::class)->find($this->request->getPost('gallery_id'));

        // Add the original image
        $image = $gallery->addAsset($file)
            ->usingFileName($file->getRandomName())
            ->withCustomProperties([
                'title' => $this->request->getPost('title'),
                'description' => $this->request->getPost('description'),
                'tags' => explode(',', $this->request->getPost('tags')),
            ])
            ->toAssetCollection(ImagesCollection::class);

        // Create and add a thumbnail
        $thumbnail = \Config\Services::image()
            ->withFile($file)
            ->resize(200, 200, true)
            ->save(WRITEPATH . 'uploads/thumbnail_' . $file->getRandomName());

        $gallery->addAsset(WRITEPATH . 'uploads/thumbnail_' . $file->getName())
            ->withCustomProperty('original_id', $image->id)
            ->toAssetCollection(ThumbnailsCollection::class);

        return redirect()->to('gallery/' . $gallery->id);
    }

    return redirect()->back()->with('error', 'Failed to upload image');
}

// In your view
public function showGallery($id)
{
    $gallery = model(Gallery::class)->find($id);
    $images = $gallery->getAssets(ImagesCollection::class);

    return view('gallery', ['gallery' => $gallery, 'images' => $images]);
}
```

## Conclusion

These examples demonstrate the flexibility and power of CodeIgniter Asset Connect. You can use these patterns to implement complex asset management features in your application.

For more advanced usage, check out the [Advanced Usage](advanced-usage.md) guide.
