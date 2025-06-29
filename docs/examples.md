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

The AssetModel provides various filtering methods:

```php
// Filter by name
$model->filterAssets(fn(AssetModel $model) => $model->filterByName('profile-picture'));

// Filter by file name
$model->filterAssets(fn(AssetModel $model) => $model->filterByFileName('profile.jpg'));

// Filter by mime type
$model->filterAssets(fn(AssetModel $model) => $model->filterByMimeType('image/jpeg'));

// Filter by size
$model->filterAssets(fn(AssetModel $model) => $model->filterBySize(1024, '>=')); // Files >= 1KB

// Filter by path
$model->filterAssets(fn(AssetModel $model) => $model->filterByPath('/uploads/images/'));

// Filter by order
$model->filterAssets(fn(AssetModel $model) => $model->filterByOrder(1));

// Filter by custom property
$model->filterAssets(fn(AssetModel $model) => $model->filterByProperty('title', 'Profile Picture'));

// Filter by property existence
$model->filterAssets(fn(AssetModel $model) => $model->filterByPropertyExists('title'));

// Filter by property containing a value
$model->filterAssets(fn(AssetModel $model) => $model->filterByPropertyContains('tags', 'profile'));

// Filter by creation date
$model->filterAssets(fn(AssetModel $model) => $model->filterByCreatedAt('2023-01-01', '>='));

// Filter by update date
$model->filterAssets(fn(AssetModel $model) => $model->filterByUpdatedAt('2023-01-01', '>='));

// Filter by name pattern
$model->filterAssets(fn(AssetModel $model) => $model->filterByNameLike('profile%'));

// Filter by file name pattern
$model->filterAssets(fn(AssetModel $model) => $model->filterByFileNameLike('%.jpg'));

// Filter by date range
$model->filterAssets(fn(AssetModel $model) => $model->filterByDateRange('2023-01-01', '2023-12-31', 'created_at'));

// Filter by collection
$model->filterAssets(fn(AssetModel $model) => $model->whereCollection(ImagesCollection::class));

// Filter by entity type
$model->filterAssets(fn(AssetModel $model) => $model->whereEntityType(User::class));
```

## Working with Collections

You can retrieve assets from a specific collection:

```php
// Get assets from a specific collection
$assets = $user->getAssets(TestKolekcija::class);
```

## Adding Assets

You can add assets to an entity with various options:

```php
// Create a File object
$file = new File('C:\Users\amelj\PhpstormProjects\platforma\public\assets\images\user\placeholder.jpg');

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
$user->deleteAssets(TestKolekcija::class);
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
use Maniaba\FileConnect\AssetCollection\FileVariants;
use Maniaba\FileConnect\Interfaces\Asset\AssetCollectionDefinitionInterface;
use Maniaba\FileConnect\Interfaces\Asset\AssetCollectionSetterInterface;
use Maniaba\FileConnect\Interfaces\Asset\FileVariantInterface;

class ImagesCollection implements AssetCollectionDefinitionInterface, FileVariantInterface
{
    public function definition(AssetCollectionSetterInterface $definition): void
    {
        $definition->allowedMimeTypes('image/jpeg', 'image/png', 'image/gif', 'image/webp')
            ->setMaxFileSize(10 * 1024 * 1024); // 10MB
    }

    public function checkAuthorization(array|Entity $entity, Asset $asset): bool
    {
        return true;
    }

    public function variants(FileVariants $variants, Asset $asset): void
    {
        // No variants needed as we'll create thumbnails separately
    }
}

class ThumbnailsCollection implements AssetCollectionDefinitionInterface, FileVariantInterface
{
    public function definition(AssetCollectionSetterInterface $definition): void
    {
        $definition->allowedMimeTypes('image/jpeg', 'image/png', 'image/gif', 'image/webp')
            ->setMaxFileSize(2 * 1024 * 1024); // 2MB
    }

    public function checkAuthorization(array|Entity $entity, Asset $asset): bool
    {
        return true;
    }

    public function variants(FileVariants $variants, Asset $asset): void
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

        // Register other collection definitions (not as default)
        $setup->setCollectionDefinition(ThumbnailsCollection::class);

        // Alternatively, you could use the simplified approach with all collections registered as non-default:
        // $setup->setCollectionDefinition(ImagesCollection::class);
        // $setup->setCollectionDefinition(ThumbnailsCollection::class);
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
