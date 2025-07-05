# Custom Asset Collections

Asset collections allow you to organize your assets into logical groups. You can create custom collections by implementing the `AssetCollectionDefinitionInterface`:

Here's a simple example of a collection that only allows image files and limits the size to 5 MB:

```php
class SingleImageCollection implements AssetCollectionDefinitionInterface, AssetVariantsInterface
{
    public function definition(AssetCollectionSetterInterface $definition): void
    {
        $definition->singleFileCollection()
            ->setMaxFileSize(5 * 1024 * 1024) // 5 MB
            ->allowedExtensions(...AssetExtension::images());
    }

    public function checkAuthorization(array|Entity $entity, Asset $asset): bool
    {
        return true;
    }

    public function variants(CreateAssetVariantsInterface $variants, Asset $asset): void
    {
        // No variants needed
    }
}
```

For more complex collections, you can customize the configuration further:

```php
class ProfilePicturesCollection implements AssetCollectionDefinitionInterface, AssetVariantsInterface
{
    public function definition(AssetCollectionSetterInterface $definition): void
    {
        // Allow specific file extensions using the AssetExtension enum
        $definition->allowedExtensions(
            AssetExtension::JPG,
            AssetExtension::PNG,
            AssetExtension::GIF,
            // You can also use string values
            'webp'
        )
        // Alternatively, you can use the spread operator with AssetExtension::images()
        // to allow all image extensions at once
        // ->allowedExtensions(...AssetExtension::images())
        // Allow specific MIME types using the AssetMimeType enum
        ->allowedMimeTypes(
            AssetMimeType::IMAGE_JPEG,
            AssetMimeType::IMAGE_PNG,
            AssetMimeType::IMAGE_GIF,
            // You can also use string values
            'image/webp'
        )
        // Set maximum file size (in bytes)
        ->setMaxFileSize(5 * 1024 * 1024) // 5MB

        // Make this a single-file collection (only one file allowed)
        // This is equivalent to calling onlyKeepLatest(1)
        ->singleFileCollection()

        // OR set a maximum number of files to keep (deletes oldest when exceeded)
        // ->onlyKeepLatest(10) // Keep only the 10 most recent files

        // Set a custom path generator for this collection
        ->setPathGenerator(new CustomPathGenerator());
    }

    public function checkAuthorization(array|Entity $entity, Asset $asset): bool
    {
        // Check if the user is authorized to access this asset
        return true;
    }

    public function variants(CreateAssetVariantsInterface $variants, Asset $asset): void
    {
        // Define file variants (e.g., thumbnails)
    }
}
```

## Understanding AssetCollectionDefinitionInterface

The `AssetCollectionDefinitionInterface` requires you to implement the following methods:

### definition

```php
public function definition(AssetCollectionSetterInterface $definition): void
```

This method is where you configure the collection's settings, such as allowed file types, maximum file size, and other constraints.

### checkAuthorization

```php
public function checkAuthorization(array|Entity $entity, Asset $asset): bool
```

This method determines whether a user is authorized to access an asset. It's called when an asset is requested through the AssetConnectController.

## Understanding AssetVariantsInterface

If your collection needs to support variants (like thumbnails or different sizes of images), you should also implement the `AssetVariantsInterface`:

### variants

```php
public function variants(CreateAssetVariantsInterface $variants, Asset $asset): void
```

This method is where you define the variants that should be created for assets in this collection.

## Available Configuration Options

The `AssetCollectionSetterInterface` provides several methods for configuring your collections:

### allowedExtensions

```php
public function allowedExtensions(string|AssetExtension ...$extensions): self
```

Specifies which file extensions are allowed in the collection. You can use the `AssetExtension` enum or string values.

### allowedMimeTypes

```php
public function allowedMimeTypes(string|AssetMimeType ...$mimeTypes): self
```

Specifies which MIME types are allowed in the collection. You can use the `AssetMimeType` enum or string values.

### setMaxFileSize

```php
public function setMaxFileSize(int $maxFileSize): self
```

Sets the maximum file size (in bytes) allowed for assets in the collection.

### singleFileCollection

```php
public function singleFileCollection(): self
```

Makes the collection a single-file collection, meaning it can only contain one asset at a time. When a new asset is added, any existing assets are automatically deleted.

### onlyKeepLatest

```php
public function onlyKeepLatest(int $count): self
```

Sets the maximum number of assets to keep in the collection. When this limit is exceeded, the oldest assets are automatically deleted.

### setPathGenerator

```php
public function setPathGenerator(PathGeneratorInterface $pathGenerator): self
```

Sets a custom path generator for the collection, which determines how file paths are generated for stored assets.

## Best Practices

When creating custom asset collections, consider the following best practices:

### Validate File Types

Always validate file types to ensure that only the expected types of files are stored in your collections. This helps prevent security issues and ensures that your application can properly handle the files.

```php
$definition->allowedExtensions(...AssetExtension::images())
    ->allowedMimeTypes(...AssetMimeType::getImageMimeTypes());
```

### Limit File Sizes

Set appropriate file size limits to prevent users from uploading excessively large files that could cause performance issues or storage problems.

```php
$definition->setMaxFileSize(10 * 1024 * 1024); // 10MB
```

### Use Single-File Collections When Appropriate

If your entity should only have one file of a certain type (like a profile picture), use a single-file collection to automatically handle the replacement of old files.

```php
$definition->singleFileCollection();
```

### Implement Proper Authorization

Always implement proper authorization checks in the `checkAuthorization` method to ensure that only authorized users can access assets.

```php
public function checkAuthorization(array|Entity $entity, Asset $asset): bool
{
    // Check if the user is authorized to access this asset
    $user = service('auth')->user();
    return $user->id === $entity->user_id || $user->isAdmin();
}
```

## Conclusion

Custom asset collections provide a powerful way to organize and manage files in your application. By implementing the `AssetCollectionDefinitionInterface` and configuring your collections appropriately, you can ensure that your application handles files in a secure, efficient, and organized manner.
