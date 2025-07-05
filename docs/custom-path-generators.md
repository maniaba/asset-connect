# Custom Path Generators

Path generators determine how file paths are generated for stored assets. This page explains how to create and customize path generators to suit your needs.

## Creating Custom Path Generators

Path generators determine how file paths are generated for stored assets. You can create custom path generators by implementing the `PathGeneratorInterface`:

```php
class CustomPathGenerator implements PathGeneratorInterface
{
    // Get the path for the given asset, relative to the root storage path.
    // It's important to generate unique paths to prevent file overwrites.
    public function getPath(PathGeneratorHelper $generatorHelper, AssetCollectionGetterInterface $collection): string
    {
        // Check if the collection is protected (non-public) or public
        $isProtected = $collection->getVisibility() === AssetVisibility::PROTECTED;

        // Set the base path based on visibility
        $basePath = $isProtected ? WRITEPATH : realpath(ROOTPATH . 'public') . DIRECTORY_SEPARATOR;

        // Generate a unique path using helper methods
        // This creates a structure like: assets/2023-05-25/123456.789/
        return $basePath . $generatorHelper->getPathString(
            'assets',
            $generatorHelper->getDateTime(),
            $generatorHelper->getUniqueId()
        );
    }

    // Get the path for variants (e.g., thumbnails) of the given asset.
    // This should also generate unique paths.
    public function getPathForVariants(PathGeneratorHelper $generatorHelper, AssetCollectionGetterInterface $collection): string
    {
        // Get the base path from the getPath method
        $basePath = $this->getPath($generatorHelper, $collection);

        // Add a 'variants' subdirectory
        return $basePath . $generatorHelper->getPathString('variants');
    }

    // This method is called when a directory is created by the system.
    // You can use it to perform additional operations on the directory,
    // such as setting permissions, creating additional subdirectories,
    // or logging directory creation events.
    public function onCreatedDirectory(string $path): void
    {
        // Example: Set specific permissions on the created directory
        // chmod($path, 0755);

        // Example: Log directory creation
        // log_message('info', 'Directory created: ' . $path);

        // Example: Create additional subdirectories if needed
        // mkdir($path . 'thumbnails', 0755, true);

        // Example: Create an index.html file to prevent directory listing
        // file_put_contents($path . 'index.html', '<html><head><title>403 Forbidden</title></head><body><p>Directory access is forbidden.</p></body></html>');

        // Example: Create a .htaccess file to restrict direct access
        // file_put_contents($path . '.htaccess', "Options -Indexes\nDeny from all");
    }
}
```

## Securing Asset Directories

When storing assets, especially in public directories, it's important to consider security. The `onCreatedDirectory` method provides an excellent opportunity to add security measures to your asset directories. Here are two common approaches:

### Creating index.html Files

Adding an index.html file to each directory prevents users from browsing the directory contents directly. If someone tries to access the directory in a browser, they'll see the contents of the index.html file instead of a listing of all files:

```php
// Create an index.html file to prevent directory listing
file_put_contents($path . 'index.html', '<html><head><title>403 Forbidden</title></head><body><p>Directory access is forbidden.</p></body></html>');
```

This is a simple and effective approach that works on virtually all web servers.

### Creating .htaccess Files

For Apache servers, you can create a .htaccess file to have more control over directory access:

```php
// Create a .htaccess file to restrict direct access
file_put_contents($path . '.htaccess', "Options -Indexes\nDeny from all");
```

This .htaccess file does two things:
1. `Options -Indexes` - Prevents directory listing
2. `Deny from all` - Blocks direct access to files in this directory

Note that when using .htaccess to deny all access, you'll need to create specific rules in your application's main .htaccess file to allow access to the files through your application's controllers.

## Understanding PathGeneratorHelper

The `PathGeneratorHelper` class provides several useful methods for generating unique paths:

1. `getUniqueId(bool $moreEntropy = false)`: Generates a unique ID, with an option for more entropy.
   ```php
   // Basic unique ID
   $uniqueId = $generatorHelper->getUniqueId(); // e.g., "1620000000_60a1b2c3"

   // More secure unique ID with higher entropy
   $secureId = $generatorHelper->getUniqueId(true); // SHA-256 hash
   ```

2. `getDateTime()`: Generates a date-time string formatted as a folder name.
   ```php
   $dateTimeFolder = $generatorHelper->getDateTime(); // e.g., "2023-05-25/123456.789/"
   ```

3. `getTime()`: Gets the current time formatted as a string.
   ```php
   $timeString = $generatorHelper->getTime(); // e.g., "123456.789"
   ```

4. `getPathString(string ...$segments)`: Joins path segments with the system's directory separator.
   ```php
   $path = $generatorHelper->getPathString('folder1', 'folder2', 'folder3'); // "folder1/folder2/folder3/"
   ```

## Understanding AssetCollectionGetterInterface

The `AssetCollectionGetterInterface` is passed to the path generator methods and provides information about the asset collection:

1. `getVisibility()`: Returns the visibility of the collection (PUBLIC or PROTECTED).
   ```php
   $visibility = $collection->getVisibility(); // AssetVisibility::PUBLIC or AssetVisibility::PROTECTED
   ```

2. `getMaximumNumberOfItemsInCollection()`: Returns the maximum number of items allowed in the collection.
3. `getMaxFileSize()`: Returns the maximum file size allowed in the collection.
4. `isSingleFileCollection()`: Returns whether the collection is limited to a single file.
5. `getAllowedMimeTypes()`: Returns an array of allowed MIME types.
6. `getAllowedExtensions()`: Returns an array of allowed file extensions.

These methods can be useful when generating paths, especially `getVisibility()` which helps determine whether to store files in a public or protected location.

## Importance of Unique Paths

When implementing custom path generators, it's crucial to ensure that each asset gets a unique path. This prevents files from overwriting each other, especially in high-traffic applications where multiple files might be uploaded simultaneously.

The `PathGeneratorHelper` class provides methods like `getUniqueId()` and `getDateTime()` specifically to help generate unique paths. By combining these with other identifiers (like collection names, entity IDs, etc.), you can create a robust path generation strategy that minimizes the risk of collisions.
