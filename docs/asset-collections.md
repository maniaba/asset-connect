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

### AssetVariantsInterface

The `AssetVariantsInterface` allows you to define variants of assets, such as thumbnails or resized images. It defines a single method:

```php
public function variants(CreateAssetVariantsInterface $variants, Asset $asset): void;
```

This method is called when an asset is added to the collection. You can use the provided `CreateAssetVariantsInterface` instance to create variants of the asset.

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

## CreateAssetVariantsInterface

The `CreateAssetVariantsInterface` is implemented by classes that provide methods for creating asset variants. It's used in the `variants` method of `AssetVariantsInterface`. It defines the following method:

```php
public function assetVariant(string $name, Closure $closure): ?AssetVariant;
```

This method allows you to create a new asset variant with the given name and closure. The closure receives an `AssetVariant` and an `Asset` and is used to define how to process the variant.

## AssetVariants Class

The `AssetVariants` class implements the `CreateAssetVariantsInterface` and provides methods for creating asset variants. It has a property `onQueue` that can be set to true to indicate that variants should be processed on a queue:

```php
public bool $onQueue = false;
```

When `onQueue` is set to true, the variants will be processed asynchronously using a queue job, which can improve performance for large files or complex processing.

## Creating Custom Asset Collections

To create a custom asset collection, you need to implement the `AssetCollectionDefinitionInterface` and optionally the `AssetVariantsInterface` and/or `AuthorizableAssetCollectionDefinitionInterface`.

Here's an example of a custom asset collection that implements all three interfaces:

```php
class ProfilePicturesCollection implements AuthorizableAssetCollectionDefinitionInterface, AssetVariantsInterface
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

    public function variants(CreateAssetVariantsInterface $variants, Asset $asset): void
    {
        $variants->onQueue = true; // Indicates that file variants should be processed on a queue.

        // Create variants of the asset
        // For example, create a thumbnail
        if ($asset->isImage()) {
            // Create a thumbnail variant
            $variants->assetVariant('thumbnail', static function (AssetVariant $variant, Asset $asset): void {
                // Use an image manipulation library to create the thumbnail
                $imageService = \Config\Services::image();
                $imageService->withFile($asset->path)
                    ->fit(300, 300, 'center')
                    ->text('Thumbnail')
                    ->save($variant->path);

                // Alternatively, you can use the writeFile method to write the file directly
                // $variant->writeFile('thumbnail data');
            });
        }
    }
}
```

## Using Custom Asset Collections

Once you've created a custom asset collection, you can use it in your entity's `setupAssetConnect` method:

```php
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
