# FileConnect for CodeIgniter 4

FileConnect is a file management library for CodeIgniter 4 that allows you to associate files with any entity in your application.

## Installation

You can install the package via composer:

```bash
composer require maniaba/file-connect
```

## Usage

### Setup

1. Run the migration to create the assets table:

```bash
php spark migrate --namespace=Maniaba\\FileConnect
```

2. Add the `UseAssetConnectTrait` to any entity you want to associate files with:

```php
use CodeIgniter\Entity\Entity;
use Maniaba\FileConnect\Traits\UseAssetConnectTrait;
use Maniaba\FileConnect\Interfaces\AssetCollection\SetupAssetCollection;
use App\AssetCollections\ImagesCollection;
use App\AssetCollections\DocumentsCollection;

class User extends Entity
{
    use UseAssetConnectTrait;

    // You must implement this abstract method
    public function setupAssetConnect(SetupAssetCollection $setup): void
    {
        // Set the default collection definition
        // Note: Only one default collection can be set; additional calls will override previous ones
        $setup->setDefaultCollectionDefinition(ImagesCollection::class);

        // You can also register other collection definitions (not as default)
        $setup->setCollectionDefinition(DocumentsCollection::class);
    }
}
```

### Adding Assets

You can add assets to an entity using the `addAsset` method, which returns an `AssetAdder` instance that you can configure and then add to a collection:

```php
// Add an asset from a file path
$asset = $user->addAsset('/path/to/file.jpg')->toAssetCollection();

// Add an asset from a CodeIgniter File object
$file = new \CodeIgniter\Files\File('/path/to/file.jpg');
$asset = $user->addAsset($file)->toAssetCollection();

// Add an asset with custom properties
$asset = $user->addAsset('/path/to/file.jpg')
    ->withCustomProperties([
        'title' => 'My File',
        'description' => 'This is my file',
    ])
    ->toAssetCollection();

// Add an asset to a specific collection
$asset = $user->addAsset('/path/to/file.jpg')
    ->toAssetCollection(ImagesCollection::class);

// Add an asset with custom properties to a specific collection
$asset = $user->addAsset('/path/to/file.jpg')
    ->withCustomProperties([
        'title' => 'My File',
        'description' => 'This is my file',
    ])
    ->toAssetCollection(ImagesCollection::class);
```

### Retrieving Assets

You can retrieve assets from an entity using the `getAssets` method:

```php
// Get all assets
$assets = $user->getAssets();

// Get assets from a specific collection
$images = $user->getAssets(ImagesCollection::class);

// Get the first asset
$asset = $user->getFirstAsset();

// Get the first asset from a specific collection
$image = $user->getFirstAsset(ImagesCollection::class);
```

### Working with Collections

You can work with collections using the `collection` method:

```php
// Get a collection
$collection = $user->collection(ImagesCollection::class);

// Add an asset to the collection
$asset = $collection->addAsset('/path/to/file.jpg')->toAssetCollection();

// Add an asset with custom properties to the collection
$asset = $collection->addAsset('/path/to/file.jpg')
    ->withCustomProperties([
        'title' => 'My File',
        'description' => 'This is my file',
    ])
    ->toAssetCollection();

// Get all assets in the collection
$assets = $collection->getAssets();

// Get the first asset in the collection
$asset = $collection->getFirstAsset();

// Delete all assets in the collection
$collection->deleteAssets();
```

### Working with Assets

The `Asset` entity provides methods for working with assets:

```php
// Get the absolute path to the asset file
$path = $asset->getAbsolutePath();

// Get the relative path to the asset file
$path = $asset->getRelativePath();

// Get the URL to the asset file
$url = $asset->getUrl();

// Get the custom properties of the asset
$properties = $asset->getCustomProperties();

// Get a specific custom property of the asset
$title = $asset->getCustomProperty('title');

// Get the file name of the asset
$fileName = $asset->getFileName();

// Get the mime type of the asset
$mimeType = $asset->getMimeType();

// Get the size of the asset in bytes
$size = $asset->getSize();

// Get the human-readable size of the asset
$size = $asset->getHumanReadableSize();

// Get the collection name of the asset
$collection = $asset->getCollection();

// Check if the asset is an image
if ($asset->isImage()) {
    // ...
}

// Check if the asset is a video
if ($asset->isVideo()) {
    // ...
}

// Check if the asset is a document
if ($asset->isDocument()) {
    // ...
}
```

### Deleting Assets

You can delete assets from an entity using the `deleteAssets` method:

```php
// Delete all assets
$user->deleteAssets();

// Delete assets from a specific collection
$user->deleteAssets(ImagesCollection::class);
```

## Enums

The library provides several enums for working with assets:


### Secure Asset Storage

For secure asset storage, you can implement the `AuthorizableAssetCollectionDefinitionInterface`:

```php
use CodeIgniter\Entity\Entity;
use Maniaba\FileConnect\Asset\Asset;
use Maniaba\FileConnect\Interfaces\Asset\AuthorizableAssetCollectionDefinitionInterface;

class SecureDocumentsCollection implements AuthorizableAssetCollectionDefinitionInterface
{
    public function definition(AssetCollectionSetterInterface $definition): void
    {
        // Configure the collection
        $definition->allowedMimeTypes('application/pdf', 'application/msword');
    }

    public function checkAuthorization(array|Entity $entity, Asset $asset): bool
    {
        // Check if the user is authorized to access this asset
        return $entity->id === $asset->entity_id;
    }

    public function variants(FileVariants $variants, Asset $asset): void
    {
        // No variants needed for documents
    }
}
```

This allows you to store assets in non-public locations (like the "writable" folder) and control access through your controllers.

### AssetMimeType

```php
use Maniaba\FileConnect\Enums\AssetMimeType;

// Available mime types (examples)
AssetMimeType::IMAGE_JPEG; // 'image/jpeg'
AssetMimeType::PDF; // 'application/pdf'
AssetMimeType::VIDEO_MP4; // 'video/mp4'

// Get the file extension for a mime type
$extension = AssetMimeType::getExtension(AssetMimeType::IMAGE_JPEG); // 'jpg'

// Check if a mime type is an image
if (AssetMimeType::isImage(AssetMimeType::IMAGE_JPEG)) {
    // ...
}

// Get a mime type from a file extension
$mimeType = AssetMimeType::fromExtension('jpg'); // 'image/jpeg'

// You can also use any MIME type supported by CodeIgniter
$extension = AssetMimeType::getExtension('application/vnd.ms-excel'); // 'xls'
$mimeType = AssetMimeType::fromExtension('xlsx'); // 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
```

## Configuration

You can configure the library by editing the `app/Config/Asset.php` file:

```php
public ?string $DBGroup = null; // The database group to use

public array $tables = [
    'assets' => 'assets', // The name of the assets table
];
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
