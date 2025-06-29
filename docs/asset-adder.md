# Asset Adder

The `AssetAdder` class is a core component of CodeIgniter Asset Connect that handles the process of adding assets to entities. It provides a fluent interface for configuring assets before they are stored in a collection.

## What is AssetAdder?

When you call the `addAsset()` method on an entity that uses the `UseAssetConnectTrait`, it returns an instance of the `AssetAdder` class. This class allows you to configure various aspects of the asset, such as:

- File name and display name
- Custom properties
- File name sanitization
- Order within the collection
- Whether to preserve the original file

After configuring the asset, you can store it in a collection using the `toAssetCollection()` method.


## Methods

The `AssetAdder` class provides several methods for configuring assets before they are stored in a collection.

### preservingOriginal

```php
public function preservingOriginal(bool $preserveOriginal = true): self
```

Sets whether to preserve the original file after it has been processed and stored. By default, original files are not preserved.

**Parameters:**
- `$preserveOriginal`: Whether to preserve the original file. Defaults to `true`.

**Returns:**
- The `AssetAdder` instance for method chaining.

**Example:**
```php
$asset = $user->addAsset('/path/to/file.jpg')
    ->preservingOriginal() // Keep the original file
    ->toAssetCollection();
```

### setOrder

```php
public function setOrder(int $order): self
```

Sets the order of the asset within the collection. This can be used to control the order in which assets are displayed.

**Parameters:**
- `$order`: The order to set for the asset.

**Returns:**
- The `AssetAdder` instance for method chaining.

**Example:**
```php
$asset = $user->addAsset('/path/to/file.jpg')
    ->setOrder(1) // Set the order to 1
    ->toAssetCollection();
```

### usingFileName

```php
public function usingFileName(string $fileName): self
```

Sets the file name of the asset. This is the name that will be used when storing the file.

**Parameters:**
- `$fileName`: The file name to set for the asset.

**Returns:**
- The `AssetAdder` instance for method chaining.

**Example:**
```php
$asset = $user->addAsset('/path/to/file.jpg')
    ->usingFileName('custom-file-name.jpg') // Set a custom file name
    ->toAssetCollection();
```

### usingName

```php
public function usingName(string $name): self
```

Sets the display name of the asset. This is the name that will be used when displaying the asset in the application.

**Parameters:**
- `$name`: The display name to set for the asset.

**Returns:**
- The `AssetAdder` instance for method chaining.

**Example:**
```php
$asset = $user->addAsset('/path/to/file.jpg')
    ->usingName('Profile Picture') // Set a display name
    ->toAssetCollection();
```

### sanitizingFileName

```php
public function sanitizingFileName(callable $fileNameSanitizer): self
```

Sets a custom file name sanitizer. This is a callable that takes a string (the file name) and returns a sanitized string.

**Parameters:**
- `$fileNameSanitizer`: A callable that takes a string and returns a sanitized string.

**Returns:**
- The `AssetAdder` instance for method chaining.

**Example:**
```php
$asset = $user->addAsset('/path/to/file.jpg')
    ->sanitizingFileName(function (string $fileName): string {
        return str_replace(['#', '/', '\\', ' '], '-', $fileName);
    }) // Set a custom file name sanitizer
    ->toAssetCollection();
```

### withCustomProperty

```php
public function withCustomProperty(string $key, mixed $value): self
```

Adds a custom property to the asset. Custom properties can be used to store additional information about the asset.

**Parameters:**
- `$key`: The key for the custom property.
- `$value`: The value for the custom property.

**Returns:**
- The `AssetAdder` instance for method chaining.

**Example:**
```php
$asset = $user->addAsset('/path/to/file.jpg')
    ->withCustomProperty('title', 'Profile Picture') // Add a custom property
    ->toAssetCollection();
```

### withCustomProperties

```php
public function withCustomProperties(array $customProperties): self
```

Adds multiple custom properties to the asset. Custom properties can be used to store additional information about the asset.

**Parameters:**
- `$customProperties`: An associative array of custom properties.

**Returns:**
- The `AssetAdder` instance for method chaining.

**Example:**
```php
$asset = $user->addAsset('/path/to/file.jpg')
    ->withCustomProperties([
        'title' => 'Profile Picture',
        'description' => 'User profile picture',
        'tags' => ['profile', 'user'],
    ]) // Add multiple custom properties
    ->toAssetCollection();
```

### toAssetCollection

```php
public function toAssetCollection(AssetCollectionDefinitionInterface|string|null $collection = null): Asset
```

Stores the asset in the specified collection and returns the stored asset.

**Parameters:**
- `$collection`: The collection to store the asset in. This can be an instance of a class implementing `AssetCollectionDefinitionInterface`, a string representing the class name, or `null` to use the default collection.

**Returns:**
- The stored `Asset` instance.

**Throws:**
- `AssetException`: If there is an error storing the asset.
- `FileException`: If there is an error with the file.
- `InvalidArgumentException`: If the collection is invalid.
- `Throwable`: If any other error occurs.

**Example:**
```php
// Store in the default collection
$asset = $user->addAsset('/path/to/file.jpg')
    ->toAssetCollection();

// Store in a specific collection
$asset = $user->addAsset('/path/to/file.jpg')
    ->toAssetCollection(ImagesCollection::class);
```

## Complete Examples

### Basic Usage

```php
// Add an asset from a file path
$asset = $user->addAsset('/path/to/file.jpg')
    ->toAssetCollection();
```

### Advanced Usage

```php
// Add an asset with various configurations
$asset = $user->addAsset('/path/to/file.jpg')
    ->usingFileName('custom-file-name.jpg') // Set a custom file name
    ->usingName('Profile Picture') // Set a display name
    ->setOrder(1) // Set the order
    ->preservingOriginal() // Keep the original file
    ->sanitizingFileName(function (string $fileName): string {
        return str_replace(['#', '/', '\\', ' '], '-', $fileName);
    }) // Set a custom file name sanitizer
    ->withCustomProperties([
        'title' => 'Profile Picture',
        'description' => 'User profile picture',
        'tags' => ['profile', 'user'],
    ]) // Add custom properties
    ->toAssetCollection(ImagesCollection::class); // Store in a specific collection
```

### Working with Uploaded Files

```php
// In a controller method
public function uploadProfilePicture()
{
    $file = $this->request->getFile('profile_picture');

    if ($file->isValid() && !$file->hasMoved()) {
        $user = model(User::class)->find($this->request->getPost('user_id'));

        // Add the uploaded file as an asset
        $asset = $user->addAsset($file)
            ->usingFileName($file->getRandomName()) // Use a random name to avoid conflicts
            ->usingName('Profile Picture')
            ->withCustomProperties([
                'uploaded_by' => user_id(),
                'uploaded_at' => date('Y-m-d H:i:s'),
            ])
            ->toAssetCollection(ProfilePicturesCollection::class);

        return redirect()->to('user/profile')->with('success', 'Profile picture uploaded successfully.');
    }

    return redirect()->back()->with('error', 'Failed to upload profile picture.');
}
```

## Best Practices

### File Name Sanitization

Always sanitize file names to ensure they are safe to use in file systems and URLs. The `AssetAdder` class provides a default file name sanitizer, but you can override it with your own implementation using the `sanitizingFileName` method.

```php
$asset = $user->addAsset('/path/to/file.jpg')
    ->sanitizingFileName(function (string $fileName): string {
        // Remove special characters and spaces
        $fileName = preg_replace('/[^\w\.-]/', '-', $fileName);
        // Convert to lowercase
        $fileName = strtolower($fileName);
        // Ensure the file name is not too long
        $fileName = substr($fileName, 0, 100);
        return $fileName;
    })
    ->toAssetCollection();
```

### Preserving Original Files

By default, the original file is not preserved after it has been processed and stored. If you need to keep the original file, use the `preservingOriginal` method.

```php
$asset = $user->addAsset('/path/to/file.jpg')
    ->preservingOriginal() // Keep the original file
    ->toAssetCollection();
```

### Custom Properties

Use custom properties to store additional information about the asset. This can be useful for filtering, sorting, and displaying assets.

```php
$asset = $user->addAsset('/path/to/file.jpg')
    ->withCustomProperties([
        'title' => 'Profile Picture',
        'description' => 'User profile picture',
        'tags' => ['profile', 'user'],
        'visibility' => 'public',
        'expires_at' => date('Y-m-d', strtotime('+30 days')),
    ])
    ->toAssetCollection();
```

## Conclusion

The `AssetAdder` class is a powerful component of CodeIgniter Asset Connect that provides a fluent interface for configuring assets before they are stored in a collection. By using its methods, you can customize various aspects of the asset, such as file name, display name, custom properties, and more.
