# Basic Usage

This guide covers the fundamental operations you can perform with CodeIgniter Asset Connect.

## Working with Entities

### Setting Up an Entity

To use Asset Connect with an entity, you need to add the `UseAssetConnectTrait` to your entity class and implement the `setupAssetConnect` method:

```php
<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;
use Maniaba\FileConnect\Traits\UseAssetConnectTrait;
use Maniaba\FileConnect\Interfaces\AssetCollection\SetupAssetCollection;
use App\AssetCollections\ImagesCollection;

class Product extends Entity
{
    use UseAssetConnectTrait;

    public function setupAssetConnect(SetupAssetCollection $setup): void
    {
        // Set the default collection definition
        // Note: Only one default collection can be set; additional calls will override previous ones
        $setup->setDefaultCollectionDefinition(ImagesCollection::class);

    }
}

// Example of a custom collection class
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
        // Configure the collection using the setter interface
        $definition
            // Allow specific file extensions using the AssetExtension enum
            ->allowedExtensions(
                AssetExtension::JPG,
                AssetExtension::PNG,
                AssetExtension::GIF
            )
            // Alternatively, you can use the spread operator with AssetExtension::images()
            // to allow all image extensions at once:
            // ->allowedExtensions(...AssetExtension::images())
            // Allow specific MIME types using the AssetMimeType enum
            ->allowedMimeTypes(
                AssetMimeType::IMAGE_JPEG,
                AssetMimeType::IMAGE_PNG,
                AssetMimeType::IMAGE_GIF
            )
            // Set maximum file size (in bytes)
            ->setMaxFileSize(5 * 1024 * 1024); // 5MB
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

### Understanding SetupAssetCollection Interface

The `SetupAssetCollection` interface provides methods to configure how assets are handled for an entity. Here's an explanation of each method:


#### setPathGenerator

```php
$setup->setPathGenerator(CustomPathGenerator::class);
```

This method sets the path generator for the asset collection. The path generator determines how file paths are generated for stored assets. It accepts either an instance of a class implementing `PathGeneratorInterface` or a string representing the class name.

#### setFileNameSanitizer

```php
$setup->setFileNameSanitizer(function (string $fileName): string {
    return str_replace(['#', '/', '\\', ' '], '-', $fileName);
});
```

This method sets a closure to sanitize file names before they are stored. The closure should accept a string (the file name) and return a sanitized string. This method replaces the default sanitizer.

#### setPreserveOriginal

```php
$setup->setPreserveOriginal(true);
```

This method determines whether to preserve the original file after it has been processed and stored. By default, original files are not preserved.

#### setSubjectPrimaryKeyAttribute

```php
$setup->setSubjectPrimaryKeyAttribute('user_id');
```

This method sets the primary key attribute for the subject of the asset collection. By default, the system tries to automatically detect it from the model's `$primaryKey` property, but you can override it with this method.

#### autoDetectSubjectPrimaryKeyAttribute

```php
$setup->autoDetectSubjectPrimaryKeyAttribute(UserModel::class);
```

This method automatically detects the primary key attribute from the specified model class. It's useful when you want to ensure the correct primary key is used without hardcoding it.

### Adding Assets

You can add assets to an entity using the `addAsset` method:

```php
// Add an asset from a file path
$asset = $product->addAsset('/path/to/image.jpg')->toAssetCollection();

// Add an asset to a specific collection
$asset = $product->addAsset('/path/to/manual.pdf')
    ->toAssetCollection(DocumentsCollection::class);

// Add an asset with custom properties
$asset = $product->addAsset('/path/to/video.mp4')
    ->withCustomProperties([
        'title' => 'Product Demo',
        'description' => 'A demonstration of the product features',
        'duration' => '2:30',
    ])
    ->toAssetCollection(VideosCollection::class);
```

### Retrieving Assets

You can retrieve assets from an entity using various methods:

```php
// Get all assets
$allAssets = $product->getAssets();

// Get assets from a specific collection
$images = $product->getAssets(ImagesCollection::class);

// Get the first asset
$firstAsset = $product->getFirstAsset();

// Get the first asset from a specific collection
$firstImage = $product->getFirstAsset(ImagesCollection::class);

// Get the last asset from a specific collection
$lastDocument = $product->getLastAsset(DocumentsCollection::class);
```

### Deleting Assets

You can delete assets from an entity:

```php
// Delete all assets
$product->deleteAssets();

// Delete assets from a specific collection
$product->deleteAssets(ImagesCollection::class);
```

## Working with Collections

Asset collections provide a way to organize your assets into logical groups. You can work with collections directly:

```php
// Get a collection
$imagesCollection = $product->collection(ImagesCollection::class);

// Add an asset to the collection
$asset = $imagesCollection->addAsset('/path/to/image.jpg')->toAssetCollection();

// Get all assets in the collection
$images = $imagesCollection->getAssets();

// Get the first asset in the collection
$firstImage = $imagesCollection->getFirstAsset();

// Delete all assets in the collection
$imagesCollection->deleteAssets();
```

## Working with Assets

The `Asset` entity provides methods for working with individual assets:

```php
// Get an asset
$asset = $product->getFirstAsset(ImagesCollection::class);

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

## Using Enums

Asset Connect provides several enums for working with assets:


### Secure Asset Storage

When you need to store assets in a non-public location (like the "writable" folder), you can implement the `AuthorizableAssetCollectionDefinitionInterface`. This interface adds access control to your asset collections, requiring users to go through a controller to access the files:

```php
use CodeIgniter\Entity\Entity;
use Maniaba\FileConnect\Asset\Asset;
use Maniaba\FileConnect\Enums\AssetExtension;
use Maniaba\FileConnect\Enums\AssetMimeType;
use Maniaba\FileConnect\Interfaces\Asset\AuthorizableAssetCollectionDefinitionInterface;

class SecureDocumentsCollection implements AuthorizableAssetCollectionDefinitionInterface
{
    public function definition(AssetCollectionSetterInterface $definition): void
    {
        // Configure the collection
        $definition
            // Allow specific file extensions using the AssetExtension enum
            ->allowedExtensions(
                AssetExtension::PDF,
                AssetExtension::DOC,
                AssetExtension::DOCX
            )
            // Allow specific MIME types using the AssetMimeType enum
            ->allowedMimeTypes(
                AssetMimeType::APPLICATION_PDF,
                AssetMimeType::APPLICATION_MSWORD,
                AssetMimeType::APPLICATION_DOCX
            );
    }

    public function checkAuthorization(array|Entity $entity, Asset $asset): bool
    {
        // Check if the user is authorized to access this asset
        // For example, check if the user owns the asset or has the right permissions
        return $entity->id === $asset->entity_id;
    }

    public function variants(FileVariants $variants, Asset $asset): void
    {
        // No variants needed for documents
    }
}
```

### AssetExtension

```php
use Maniaba\FileConnect\Enums\AssetExtension;

// Available file extensions (examples)
AssetExtension::JPG;  // 'jpg'
AssetExtension::PNG;  // 'png'
AssetExtension::PDF;  // 'pdf'
AssetExtension::MP4;  // 'mp4'

// Get all extensions of a specific category
$imageExtensions = AssetExtension::images(); // Returns array of image extension enums
$documentExtensions = AssetExtension::documents(); // Returns array of document extension enums
$videoExtensions = AssetExtension::videos(); // Returns array of video extension enums

// Get more specific subcategories
$vectorGraphics = AssetExtension::vectorGraphics(); // SVG, AI, EPS, CDR
$rasterGraphics = AssetExtension::rasterGraphics(); // JPG, PNG, GIF, etc.
$spreadsheets = AssetExtension::spreadsheets(); // XLS, XLSX, ODS, CSV
$presentations = AssetExtension::presentations(); // PPT, PPTX, ODP
```

### AssetMimeType

```php
use Maniaba\FileConnect\Enums\AssetMimeType;

// Available mime types (examples)
AssetMimeType::IMAGE_JPEG;         // 'image/jpeg'
AssetMimeType::APPLICATION_PDF;    // 'application/pdf'
AssetMimeType::VIDEO_MP4;          // 'video/mp4'
AssetMimeType::AUDIO_MP3;          // 'audio/mpeg'
AssetMimeType::APPLICATION_ZIP;    // 'application/zip'

// Get the file extension for a mime type
$extension = AssetMimeType::getExtension(AssetMimeType::IMAGE_JPEG); // 'jpg'
$extension = AssetMimeType::getMimeTypeExtension(AssetMimeType::IMAGE_JPEG); // 'jpg' (alternative method)

// Check if a mime type belongs to a specific category
if (AssetMimeType::isImage(AssetMimeType::IMAGE_JPEG)) {
    // Do something with the image
}
if (AssetMimeType::isDocument(AssetMimeType::APPLICATION_PDF)) {
    // Do something with the document
}
if (AssetMimeType::isVideo(AssetMimeType::VIDEO_MP4)) {
    // Do something with the video
}
if (AssetMimeType::isAudio(AssetMimeType::AUDIO_MP3)) {
    // Do something with the audio
}

// More specific category checks
if (AssetMimeType::isVectorGraphic(AssetMimeType::IMAGE_SVG)) {
    // Do something with the vector graphic
}
if (AssetMimeType::isSpreadsheet(AssetMimeType::APPLICATION_XLSX)) {
    // Do something with the spreadsheet
}

// Get a mime type from a file extension
$mimeType = AssetMimeType::fromExtension('jpg'); // 'image/jpeg'

// Get a mime type from an AssetExtension enum
$mimeType = AssetMimeType::fromAssetExtension(AssetExtension::JPG); // 'image/jpeg'
```

## Advanced Usage

For more advanced usage scenarios, check out the following topics:

- [Custom Asset Collections](configuration.md#creating-custom-asset-collections)
- [Custom Path Generators](configuration.md#creating-custom-path-generators)
- [Troubleshooting](troubleshooting.md)
