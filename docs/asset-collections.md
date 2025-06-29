# Asset Collections

Asset Collections are a core concept in CodeIgniter Asset Connect that allow you to organize your assets into logical groups. This page explains asset collections in detail and documents the interfaces used to define them.

## What are Asset Collections?

Asset Collections provide a way to group related assets together. For example, you might have a "profile_pictures" collection for user profile images, a "documents" collection for PDF files, and a "videos" collection for video files.

Each collection can have its own configuration, such as:
- Allowed file types
- Maximum file size
- Path generation rules
- Authorization rules
- File variants (e.g., thumbnails)

## Core Interfaces

Asset Connect uses several interfaces to define and configure asset collections. Understanding these interfaces is essential for creating custom asset collections.

### AssetCollectionDefinitionInterface

The `AssetCollectionDefinitionInterface` is the base interface for all asset collections. It defines a single method:

```php
public function definition(AssetCollectionSetterInterface $definition): void;
```

This method is where you configure the collection using the provided `AssetCollectionSetterInterface` instance. For example, you can specify allowed MIME types, maximum file size, and other settings.

### FileVariantInterface

The `FileVariantInterface` allows you to define variants of assets, such as thumbnails or resized images. It defines a single method:

```php
public function variants(FileVariants $variants, Asset $asset): void;
```

This method is called when an asset is added to the collection. You can use the provided `FileVariants` instance to create variants of the asset.

### AuthorizableAssetCollectionDefinitionInterface

The `AuthorizableAssetCollectionDefinitionInterface` extends `AssetCollectionDefinitionInterface` and adds authorization capabilities to asset collections. It defines an additional method:

```php
public function checkAuthorization(array|Entity $entity, Asset $asset): bool;
```

This method is called when an asset is accessed. You can use it to check if the user is authorized to access the asset. For example, you might check if the user owns the asset or has the necessary permissions.

### AssetCollectionSetterInterface

The `AssetCollectionSetterInterface` provides methods for configuring asset collections. It's used in the `definition` method of `AssetCollectionDefinitionInterface`. It defines the following methods:

```php
public function allowedExtensions(AssetExtension|string ...$extensions): static;
public function allowedMimeTypes(AssetMimeType|string ...$mimeTypes): static;
public function onlyKeepLatest(int $maximumNumberOfItemsInCollection): static;
public function setMaxFileSize(float|int $maxFileSize): static;
public function singleFileCollection(): static;
public function setPathGenerator(PathGeneratorInterface $pathGenerator): static;
```

These methods allow you to:
- Specify allowed file extensions
- Specify allowed MIME types
- Limit the number of items in the collection
- Set the maximum file size
- Make the collection hold only a single file
- Set the path generator for the collection

## FileVariants Class

The `FileVariants` class provides methods for creating file variants. It's used in the `variants` method of `FileVariantInterface`. It defines the following methods:

```php
public function onQueue(?string $queue = null): FileVariants;
public function writeFile(string $name, string $data, string $mode = 'wb'): bool;
public function filePath(string $name): string;
```

These methods allow you to:
- Specify a queue for processing variants
- Write a file variant
- Get the path for a variant

## Creating Custom Asset Collections

To create a custom asset collection, you need to implement the `AssetCollectionDefinitionInterface` and optionally the `FileVariantInterface` and/or `AuthorizableAssetCollectionDefinitionInterface`.

Here's an example of a custom asset collection that implements all three interfaces:

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
use Maniaba\FileConnect\Interfaces\Asset\AuthorizableAssetCollectionDefinitionInterface;
use Maniaba\FileConnect\Interfaces\Asset\FileVariantInterface;
use Maniaba\FileConnect\PathGenerator\CustomPathGenerator;

class ProfilePicturesCollection implements AuthorizableAssetCollectionDefinitionInterface, FileVariantInterface
{
    public function definition(AssetCollectionSetterInterface $definition): void
    {
        // Configure the collection
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
            ->setMaxFileSize(5 * 1024 * 1024) // 5MB
            // Set a custom path generator for this collection
            ->setPathGenerator(new CustomPathGenerator());
    }

    public function checkAuthorization(array|Entity $entity, Asset $asset): bool
    {
        // Check if the user is authorized to access this asset
        // For example, check if the user owns the asset
        return $entity->id === $asset->entity_id;
    }

    public function variants(AssetVariants $variants, Asset $asset): void
    {
        // Create variants of the asset
        // For example, create a thumbnail
        if ($asset->isImage()) {
            // Create a thumbnail variant
            // This is just a placeholder - in a real application, you would
            // use an image manipulation library to create the thumbnail
            $variants->writeFile('thumbnail', 'thumbnail data');
        }
    }
}
```

## Using Custom Asset Collections

Once you've created a custom asset collection, you can use it in your entity's `setupAssetConnect` method:

```php
<?php

namespace App\Entities;

use App\AssetCollections\ProfilePicturesCollection;
use CodeIgniter\Entity\Entity;
use Maniaba\FileConnect\Traits\UseAssetConnectTrait;
use Maniaba\FileConnect\Interfaces\AssetCollection\SetupAssetCollection;

class User extends Entity
{
    use UseAssetConnectTrait;

    public function setupAssetConnect(SetupAssetCollection $setup): void
    {
        // Register your custom collection
        $setup->setDefaultCollectionDefinition(ProfilePicturesCollection::class);
    }
}
```

Then you can add assets to the collection:

```php
$asset = $user->addAsset('/path/to/image.jpg')
    ->withCustomProperties([
        'title' => 'Profile Picture',
        'description' => 'User profile picture'
    ])
    ->toAssetCollection(ProfilePicturesCollection::class);
```

And retrieve assets from the collection:

```php
$profilePictures = $user->getAssets(ProfilePicturesCollection::class);
```

## Default Asset Collection

Asset Connect provides a default asset collection implementation called `DefaultAssetCollection`. This collection allows all file types and has no size limit. It's used when you don't specify a collection when adding an asset:

```php
$asset = $user->addAsset('/path/to/file.jpg')->toAssetCollection();
```

You can also use it explicitly:

```php
$asset = $user->addAsset('/path/to/file.jpg')->toAssetCollection(DefaultAssetCollection::class);
```

## Conclusion

Asset Collections are a powerful feature of CodeIgniter Asset Connect that allow you to organize your assets into logical groups with their own configuration, authorization rules, and file variants. By implementing the interfaces described in this page, you can create custom asset collections tailored to your application's needs.
