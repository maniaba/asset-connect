# Asset

The `Asset` class is a core component of CodeIgniter Asset Connect that represents a file associated with an entity. It provides methods for accessing and manipulating asset properties, retrieving file information, and managing custom properties.

## What is an Asset?

An Asset in CodeIgniter Asset Connect represents a file that has been associated with an entity in your application. Each Asset contains:

- Basic file information (name, path, size, MIME type)
- Metadata about the asset (custom properties, collection information)
- Methods for accessing and manipulating the asset

Assets are stored in collections and can be associated with any entity in your application that uses the `UseAssetConnectTrait`.

## Getting URLs from Assets

One of the most common operations is retrieving URLs for assets to display or link to them in your application.

### Getting the URL for an Asset

To get the URL for an asset, use the `getUrl()` method:

```php
// Get an asset
$asset = $user->getFirstAsset();

// Get the URL to the asset
$url = $asset->getUrl();
// Output: "https://example.com/assets/images/file.jpg"
```

This returns a full URL that can be used in your views:

```php
<img src="<?= $asset->getUrl() ?>" alt="<?= $asset->name ?>">
```

### Getting URLs for Asset Variants

If your asset has variants (like thumbnails or different sizes of images), you can get the URL for a specific variant by passing the variant name to the `getUrl()` method:

```php
// Get the URL to a thumbnail variant
$thumbnailUrl = $asset->getUrl('thumbnail');
// Output: "https://example.com/assets/images/variants/thumbnail/file.jpg"

// Use it in your view
<img src="<?= $asset->getUrl('thumbnail') ?>" alt="<?= $asset->name ?> (thumbnail)">
```

### Getting Relative URLs

If you need just the relative path instead of a full URL, use the `getUrlRelative()` method:

```php
// Get the relative URL to the asset
$relativeUrl = $asset->getUrlRelative();
// Output: "/assets/images/file.jpg"

// For a variant
$thumbnailRelativeUrl = $asset->getUrlRelative('thumbnail');
// Output: "/assets/images/variants/thumbnail/file.jpg"
```

### Getting Temporary URLs

For situations where you need to generate a URL that expires after a certain time, use the `getTemporaryUrl()` method:

```php
// Import the Time class
use CodeIgniter\I18n\Time;

// Get an asset
$asset = $user->getFirstAsset();

// Create a temporary URL that expires in 1 hour
$expiration = Time::now()->addHours(1);
$temporaryUrl = $asset->getTemporaryUrl($expiration);
// Output: "https://example.com/assets/abc123def456789/images/file.jpg"

// Create a temporary URL for a variant
$thumbnailTemporaryUrl = $asset->getTemporaryUrl($expiration, 'thumbnail');
// Output: "https://example.com/assets/abc123def456789/images/variants/thumbnail/file.jpg"

// Create a temporary URL that forces download
$downloadUrl = $asset->getTemporaryUrl($expiration, null, true);
// Output: "https://example.com/assets/abc123def456789/images/file.jpg?download=force"
```

If you need just the relative path for a temporary URL, use the `getTemporaryUrlRelative()` method:

```php
$expiration = Time::now()->addHours(1);
$relativeTemporaryUrl = $asset->getTemporaryUrlRelative($expiration);
// Output: "/assets/abc123def456789/images/file.jpg"
```

## Properties

The `Asset` class has the following properties:

| Property | Type | Description                                                                    |
|----------|------|--------------------------------------------------------------------------------|
| `id` | int | Unique identifier for the asset                                                |
| `entity_type` | string | Type of the entity to which the asset belongs (md5 hash of the class name)     |
| `entity_id` | int | Identifier for the entity to which the asset belongs                           |
| `collection` | string | Name of the collection to which the asset belongs (md5 hash of the class name) |
| `name` | string | Display name of the asset                                                      |
| `file_name` | string | Name of the file associated with the asset                                     |
| `path` | string | Path to the file on the server                                                 |
| `mime_type` | string | MIME type of the file                                                          |
| `size` | int | Size of the file in bytes                                                      |
| `order` | int | Order of the asset in the collection                                           |
| `created_at` | Time | Timestamp when the asset was created                                           |
| `updated_at` | Time | Timestamp when the asset was last updated                                      |
| `deleted_at` | Time\|null | Timestamp when the asset was deleted, null if not deleted                      |
| `metadata` | AssetMetadata | Metadata about the asset                                                       |

## Methods

The `Asset` class provides several methods for accessing and manipulating asset properties.

### getExtension

```php
public function getExtension(): string
```

Gets the file extension of the asset.

**Returns:**
- The file extension as a string.

**Throws:**
- `InvalidArgumentException`: If the extension cannot be determined.

**Example:**
```php
$extension = $asset->getExtension(); // e.g., "jpg"
```

### getPathDirname

```php
protected function getPathDirname(): string
```

Gets the directory path of the file on the server.

**Returns:**
- The directory path as a string.

**Throws:**
- `InvalidArgumentException`: If the path is not set.

**Example:**
```php
$dirPath = $asset->getPathDirname(); // e.g., "/var/www/uploads/images/"
```

### getAssetCollectionDefinitionClass

```php
public function getAssetCollectionDefinitionClass(): ?string
```

Gets the class name of the asset collection definition for this asset.

**Returns:**
- The class name of the asset collection definition, or null if not set.

**Throws:**
- `InvalidArgumentException`: If the collection class does not exist or does not implement AssetCollectionDefinitionInterface.

**Example:**
```php
$collectionClass = $asset->getAssetCollectionDefinitionClass(); // e.g., "App\Collections\ImagesCollection"
```

### getAssetCollectionDefinition

```php
public function getAssetCollectionDefinition(...$definitionArguments): ?AssetCollectionDefinitionInterface
```

Gets the asset collection definition for this asset.

**Parameters:**
- `$definitionArguments`: Additional arguments to pass to the collection definition constructor.

**Returns:**
- The asset collection definition, or null if not set.

**Example:**
```php
$collectionDefinition = $asset->getAssetCollectionDefinition();
```

### getSubjectEntity

```php
public function getSubjectEntity(...$arguments): ?Entity
```

Gets the subject entity which this asset belongs to.

**Parameters:**
- `$arguments`: Arguments to pass to the entity constructor.

**Returns:**
- The entity that this asset belongs to, or null if not set.

**Example:**
```php
$entity = $asset->getSubjectEntity();
```

### getSubjectEntityClassName

```php
public function getSubjectEntityClassName(): ?string
```

Gets the class name of the subject entity which this asset belongs to.

**Returns:**
- The class name of the subject entity, or null if not set.

**Throws:**
- `InvalidArgumentException`: If the entity class does not exist or does not extend Entity.

**Example:**
```php
$entityClass = $asset->getSubjectEntityClassName(); // e.g., "App\Models\User"
```

### getCustomProperty

```php
public function getCustomProperty(string $propertyName): mixed
```

Gets a custom property value.

**Parameters:**
- `$propertyName`: The name of the custom property.

**Returns:**
- The value of the custom property, or null if not set.

**Example:**
```php
$title = $asset->getCustomProperty('title'); // e.g., "Profile Picture"
```

### setCustomProperty

```php
public function setCustomProperty(string $propertyName, mixed $value): static
```

Sets a custom property value.

**Parameters:**
- `$propertyName`: The name of the custom property.
- `$value`: The value to set.

**Returns:**
- The Asset instance for method chaining.

**Example:**
```php
$asset->setCustomProperty('title', 'New Profile Picture');
```

### getCustomProperties

```php
public function getCustomProperties(): array
```

Gets all custom properties.

**Returns:**
- An associative array of custom properties.

**Example:**
```php
$properties = $asset->getCustomProperties();
// [
//     'title' => 'Profile Picture',
//     'description' => 'User profile picture',
//     'tags' => ['profile', 'user']
// ]
```

### save

```php
public function save(): bool
```

Saves the asset to the database.

**Returns:**
- True if the asset was saved successfully, false otherwise.

**Example:**
```php
$result = $asset->save();
```

### getRelativePath

```php
protected function getRelativePath(): string
```

Gets the relative path of the file in the storage.

**Returns:**
- The relative path as a string.

**Throws:**
- `InvalidArgumentException`: If the relative path is not set.

**Example:**
```php
$relativePath = $asset->getRelativePath(); // e.g., "/uploads/images/profile.jpg"
```

### download

```php
public function download(?string $variantName = null): DownloadResponse
```

Creates a download response for the asset.

**Parameters:**
- `$variantName`: The name of the variant to download, or null for the original asset.

**Returns:**
- A DownloadResponse instance.

**Example:**
```php
$response = $asset->download(); // Download the original asset
$response = $asset->download('thumbnail'); // Download a thumbnail variant
```

### getHumanReadableSize

```php
public function getHumanReadableSize(int $precision = 2): string
```

Gets the human-readable size of the asset.

**Parameters:**
- `$precision`: The number of decimal places to include in the formatted size.

**Returns:**
- The human-readable size as a string.

**Example:**
```php
$size = $asset->getHumanReadableSize(); // e.g., "1.25 MB"
```

### getUrl

```php
public function getUrl(?string $variantName = null): string
```

Gets the URL to access the asset. See the [Getting URLs from Assets](#getting-urls-from-assets) section at the beginning of this document for detailed examples.

**Parameters:**
- `$variantName`: The name of the variant to get the URL for, or null for the original asset.

**Returns:**
- The URL as a string.

### getUrlRelative

```php
public function getUrlRelative(?string $variantName = null): string
```

Gets the relative URL to access the asset. See the [Getting URLs from Assets](#getting-urls-from-assets) section at the beginning of this document for detailed examples.

**Parameters:**
- `$variantName`: The name of the variant to get the URL for, or null for the original asset.

**Returns:**
- The relative URL as a string.

### getTemporaryUrl

```php
public function getTemporaryUrl(Time $expiration, ?string $variantName = null, bool $forceDownload = false): string
```

Gets a temporary URL for the asset that expires after the specified time. See the [Getting URLs from Assets](#getting-urls-from-assets) section at the beginning of this document for detailed examples.

**Parameters:**
- `$expiration`: The time when the URL should expire.
- `$variantName`: The name of the variant to get the URL for, or null for the original asset.
- `$forceDownload`: Whether to force the browser to download the file instead of displaying it.

**Returns:**
- The temporary URL as a string.

### getTemporaryUrlRelative

```php
public function getTemporaryUrlRelative(Time $expiration, ?string $variantName = null, bool $forceDownload = false): string
```

Gets a temporary relative URL for the asset that expires after the specified time. See the [Getting URLs from Assets](#getting-urls-from-assets) section at the beginning of this document for detailed examples.

**Parameters:**
- `$expiration`: The time when the URL should expire.
- `$variantName`: The name of the variant to get the URL for, or null for the original asset.
- `$forceDownload`: Whether to force the browser to download the file instead of displaying it.

**Returns:**
- The temporary relative URL as a string.

### isImage

```php
public function isImage(): bool
```

Checks if the asset is an image.

**Returns:**
- True if the asset is an image, false otherwise.

**Example:**
```php
if ($asset->isImage()) {
    // Do something with the image
}
```

### isVideo

```php
public function isVideo(): bool
```

Checks if the asset is a video.

**Returns:**
- True if the asset is a video, false otherwise.

**Example:**
```php
if ($asset->isVideo()) {
    // Do something with the video
}
```

### isDocument

```php
public function isDocument(): bool
```

Checks if the asset is a document.

**Returns:**
- True if the asset is a document, false otherwise.

**Example:**
```php
if ($asset->isDocument()) {
    // Do something with the document
}
```

## Complete Examples

### Basic Usage

```php
// Get an asset
$asset = $user->getFirstAsset();

// Get basic information
$id = $asset->id;
$name = $asset->name;
$fileName = $asset->file_name;
$mimeType = $asset->mime_type;
$size = $asset->size;
$readableSize = $asset->getHumanReadableSize();
$extension = $asset->getExtension();

// Get the URL to the asset
$url = $asset->getUrl();
// Output: "https://example.com/assets/images/file.jpg"

// Check the asset type
if ($asset->isImage()) {
    // Handle image asset
} elseif ($asset->isVideo()) {
    // Handle video asset
} elseif ($asset->isDocument()) {
    // Handle document asset
}
```

### Working with Custom Properties

```php
// Get an asset
$asset = $user->getFirstAsset();

// Get all custom properties
$properties = $asset->getCustomProperties();

// Get a specific custom property
$title = $asset->getCustomProperty('title');

// Set a custom property
$asset->setCustomProperty('title', 'New Title');

// Save the changes
$asset->save();
```

### Downloading Assets

```php
// Get an asset
$asset = $user->getFirstAsset();

// Create a download response for the asset
$response = $asset->download();

// Return the response to trigger the download
return $response;
```

### Working with Asset Variants

```php
// Get an asset
$asset = $user->getFirstAsset();

// Get the URL to a variant
$thumbnailUrl = $asset->getUrl('thumbnail');
// Output: "https://example.com/assets/images/variants/thumbnail/file.jpg"

// Download a variant
$response = $asset->download('thumbnail');
```

## Best Practices

### Handling Asset URLs

When displaying assets in your application, always use the `getUrl()` method to get the URL to the asset. This ensures that the URL is generated correctly based on your application's configuration.

```php
<img src="<?= $asset->getUrl() ?>" alt="<?= $asset->name ?>">
```

### Checking Asset Types

Before performing operations specific to certain file types, use the `isImage()`, `isVideo()`, and `isDocument()` methods to check the asset type.

```php
if ($asset->isImage()) {
    // Display image preview
    echo '<img src="' . $asset->getUrl() . '" alt="' . $asset->name . '">';
} elseif ($asset->isVideo()) {
    // Display video player
    echo '<video src="' . $asset->getUrl() . '" controls></video>';
} elseif ($asset->isDocument()) {
    // Display document download link
    echo '<a href="' . $asset->getUrl() . '">Download ' . $asset->name . '</a>';
}
```

### Managing Custom Properties

Custom properties are a powerful way to store additional information about assets. Use them to store metadata that is specific to your application's needs.

```php
// Add custom properties when creating an asset
$asset = $user->addAsset('/path/to/file.jpg')
    ->withCustomProperties([
        'title' => 'Profile Picture',
        'description' => 'User profile picture',
        'tags' => ['profile', 'user'],
        'visibility' => 'public',
        'expires_at' => date('Y-m-d', strtotime('+30 days')),
    ])
    ->toAssetCollection();

// Update custom properties later
$asset->setCustomProperty('visibility', 'private');
$asset->save();
```

## Conclusion

The `Asset` class is a fundamental component of CodeIgniter Asset Connect that provides a rich set of methods for working with files associated with entities in your application. By using its methods, you can access file information, manage custom properties, generate URLs, and more.
