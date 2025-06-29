# FileConnect for CodeIgniter 4

FileConnect is a file management library for CodeIgniter 4 that allows you to associate files with any entity in your application. It's inspired by [spatie/laravel-medialibrary](https://github.com/spatie/laravel-medialibrary) but built specifically for CodeIgniter 4.

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

2. Add the `HasAssetsEntityTrait` to any entity you want to associate files with:

```php
use Maniaba\FileConnect\Traits\UseAssetConnectTrait;

class User extends Entity
{
    use UseAssetConnectTrait;

    // ...
}
```

### Adding Assets

You can add assets to an entity using the `addAsset` method, which returns an `AssetAdder` instance that you can configure and then save:

```php
// Add an asset from a file path
$asset = $user->addAsset('/path/to/file.jpg')->save();

// Add an asset from a CodeIgniter File object
$file = new \CodeIgniter\Files\File('/path/to/file.jpg');
$asset = $user->addAsset($file)->save();

// Add an asset with custom properties
$asset = $user->addAsset('/path/to/file.jpg')
    ->withCustomProperties([
        'title' => 'My File',
        'description' => 'This is my file',
    ])
    ->save();

// Add an asset to a specific collection
$asset = $user->addAsset('/path/to/file.jpg')
    ->toCollection('images')
    ->save();

// Add an asset with custom properties to a specific collection
$asset = $user->addAsset('/path/to/file.jpg')
    ->withCustomProperties([
        'title' => 'My File',
        'description' => 'This is my file',
    ])
    ->toCollection('images')
    ->save();
```

### Retrieving Assets

You can retrieve assets from an entity using the `getAssets` method:

```php
// Get all assets
$assets = $user->getAssets();

// Get assets from a specific collection
$images = $user->getAssets('images');

// Get the first asset
$asset = $user->getFirstAsset();

// Get the first asset from a specific collection
$image = $user->getFirstAsset('images');
```

### Working with Collections

You can work with collections using the `collection` method:

```php
// Get a collection
$collection = $user->collection('images');

// Add an asset to the collection
$asset = $collection->addAsset('/path/to/file.jpg')->save();

// Add an asset with custom properties to the collection
$asset = $collection->addAsset('/path/to/file.jpg')
    ->withCustomProperties([
        'title' => 'My File',
        'description' => 'This is my file',
    ])
    ->save();

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
$user->deleteAssets('images');
```

## Enums

The library provides several enums for working with assets:

### AssetCollectionType

```php
use Maniaba\FileConnect\Enums\AssetCollectionType;

// Available collection types
AssetCollectionType::DEFAULT; // 'default'
AssetCollectionType::IMAGES; // 'images'
AssetCollectionType::VIDEOS; // 'videos'
AssetCollectionType::DOCUMENTS; // 'documents'
AssetCollectionType::DOWNLOADS; // 'downloads'
AssetCollectionType::UPLOADS; // 'uploads'

// Get the display name of a collection type
$displayName = AssetCollectionType::displayName(AssetCollectionType::IMAGES); // 'Images'

// Get all collection types as an array
$types = AssetCollectionType::toArray();
```

### AssetDiskType

```php
use Maniaba\FileConnect\Enums\AssetDiskType;

// Available disk types
AssetDiskType::LOCAL; // 'local'
AssetDiskType::S3; // 's3'
AssetDiskType::GOOGLE_CLOUD; // 'google_cloud'
AssetDiskType::AZURE; // 'azure'

// Get the display name of a disk type
$displayName = AssetDiskType::S3->displayName(); // 'Amazon S3'

// Get all disk types as an array
$types = AssetDiskType::toArray();
```

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
