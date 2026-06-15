# Asset

The `Asset` class is a core component of CodeIgniter Asset Connect that represents a file associated with an entity. It provides methods for accessing and manipulating asset properties, retrieving file information, and managing public custom properties and backend internal properties.

## What is an Asset?

An Asset in CodeIgniter Asset Connect represents a file that has been associated with an entity in your application. Each Asset contains:

- Basic file information (name, path, size, MIME type)
- Metadata about the asset (custom properties, internal properties, collection information)
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
| `entity_type` | string | Configured entity key for the entity class that owns the asset                 |
| `entity_id` | int | Identifier for the entity to which the asset belongs                           |
| `collection` | string | Configured collection key for the asset collection                             |
| `storage` | string | Configured storage disk where the file is stored                               |
| `name` | string | Display name of the asset                                                      |
| `file_name` | string | Name of the file associated with the asset                                     |
| `path` | string | Storage-relative file path inside the configured disk                          |
| `local_path` | string\|null | Local filesystem path when the storage disk supports it, otherwise null        |
| `path_dirname` | string | Storage-relative directory path ending with `/`                                |
| `relative_path` | string | Storage-relative path normalized with a leading `/`                            |
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

### path_dirname

```php
$asset->path_dirname
```

Gets the storage-relative directory path of the file.

**Returns:**
- The directory path as a string.

**Throws:**
- `InvalidArgumentException`: If the path is not set.

**Example:**
```php
$dirPath = $asset->path_dirname; // e.g., "images/"
```

### getCollectionDefinitionClass

```php
public function getCollectionDefinitionClass(): string
```

Gets the class name of the asset collection definition for this asset.

**Returns:**
- The class name of the asset collection definition.

**Throws:**
- `InvalidArgumentException`: If the collection class does not exist or does not implement AssetCollectionDefinitionInterface.

**Example:**
```php
$collectionClass = $asset->getCollectionDefinitionClass(); // e.g., "App\Collections\ImagesCollection"
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

### getSubjectEntityClass

```php
public function getSubjectEntityClass(): string
```

Gets the class name of the subject entity which this asset belongs to.

**Returns:**
- The class name of the subject entity.

**Throws:**
- `InvalidArgumentException`: If the entity class does not exist or does not extend Entity.

**Example:**
```php
$entityClass = $asset->getSubjectEntityClass(); // e.g., "App\Models\User"
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

### getInternalProperty

```php
public function getInternalProperty(string $propertyName): mixed
```

Gets an internal property value. Internal properties are intended for application/backend metadata and are not included in the public asset JSON representation.

**Parameters:**
- `$propertyName`: The name of the internal property.

**Returns:**
- The value of the internal property, or null if not set.

**Example:**
```php
$jobId = $asset->getInternalProperty('processing_job_id');
```

### setInternalProperty

```php
public function setInternalProperty(string $propertyName, mixed $value): static
```

Sets an internal property value.

**Parameters:**
- `$propertyName`: The name of the internal property.
- `$value`: The value to set.

**Returns:**
- The Asset instance for method chaining.

**Example:**
```php
$asset->setInternalProperty('processing_job_id', 'job-123');
```

### getInternalProperties

```php
public function getInternalProperties(): array
```

Gets all internal properties.

**Returns:**
- An associative array of internal properties.

**Example:**
```php
$properties = $asset->getInternalProperties();
// [
//     'processing_job_id' => 'job-123',
//     's3_etag' => 'etag-value'
// ]
```

### transferToStorage

```php
public function transferToStorage(string $storage, bool $withVariants = true, bool $deleteSource = true): static
```

Transfers the asset file to another configured storage disk. The storage-relative `path` stays the same, while `storage` is updated to the target disk.

**Parameters:**
- `$storage`: Target storage disk name.
- `$withVariants`: Whether existing asset variants should be transferred and updated to the target storage.
- `$deleteSource`: Whether source files should be deleted after the database update succeeds.

**Returns:**
- The Asset instance for method chaining.

**Example:**
```php
$asset->transferToStorage('protected');
```

### copyToTemporaryFile

```php
public function copyToTemporaryFile(?string $variantName = null, ?string $directory = null, string $prefix = 'asset_connect_'): string
```

Copies the original asset file, or a named variant, from its configured storage disk into a local temporary file. This is useful for remote disks where `local_path` is `null`, and for queue jobs that need to pass a real local file path to an image, video, or document processing library.

The caller owns the returned temporary file and must delete it when processing is complete.

**Parameters:**
- `$variantName`: Optional variant name to copy instead of the original asset.
- `$directory`: Optional temp directory. Defaults to `sys_get_temp_dir()`.
- `$prefix`: Prefix passed to `tempnam()`.

**Returns:**
- The local temporary file path.

**Example:**
```php
$temporaryFile = $asset->copyToTemporaryFile();

try {
    service('image')
        ->withFile($temporaryFile)
        ->fit(300, 300, 'center')
        ->save($previewPath);
} finally {
    @unlink($temporaryFile);
}
```

### withTemporaryFile

```php
public function withTemporaryFile(callable $callback, ?string $variantName = null, ?string $directory = null, string $prefix = 'asset_connect_'): mixed
```

Streams the asset file into a local temporary file, passes that path to the callback, and deletes the temporary file in a `finally` block. Prefer this method in queued processors because cleanup happens even when processing throws an exception.

**Parameters:**
- `$callback`: Callback that receives the local temporary file path.
- `$variantName`: Optional variant name to copy instead of the original asset.
- `$directory`: Optional temp directory. Defaults to `sys_get_temp_dir()`.
- `$prefix`: Prefix passed to `tempnam()`.

**Returns:**
- The callback return value.

**Example:**
```php
$asset->withTemporaryFile(static function (string $source): void {
    service('image')
        ->withFile($source)
        ->resize(1200, 900, true)
        ->save(WRITEPATH . 'cache/processed.jpg');
});
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

### relative_path

```php
$asset->relative_path
```

Gets the storage-relative path normalized with a leading `/`.

**Returns:**
- The relative path as a string.

**Throws:**
- `InvalidArgumentException`: If the relative path is not set.

**Example:**
```php
$relativePath = $asset->relative_path; // e.g., "/images/profile.jpg"
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

### Working with Internal Properties

```php
// Get an asset
$asset = $user->getFirstAsset();

// Store backend-only metadata
$asset->setInternalProperty('processing_job_id', 'job-123');
$asset->setInternalProperty('processor', ['name' => 'image-resizer', 'version' => 2]);

// Read backend-only metadata
$jobId = $asset->getInternalProperty('processing_job_id');

// Save the changes
$asset->save();
```

Internal properties are stored in `metadata.internal`. They are useful for processing state, adapter metadata, external IDs, checksums, queue IDs, and similar backend data. They are not returned from `jsonSerialize()` and are not included in `custom_properties`.

### Transferring Assets Between Storage Disks

```php
// Move the original file and existing variants to protected storage
$asset->transferToStorage('protected');

// Copy to another disk and keep the original source files
$asset->transferToStorage('s3_public', deleteSource: false);

// Move only the original file and leave variant files on their current disks
$asset->transferToStorage('archive', withVariants: false);
```

The transfer uses storage streams, so it works for local disks, S3/MinIO, and other Flysystem adapters. If variants are not processed yet, their metadata is updated to the target storage so queued variant processors write them to the new disk.

### Processing Remote Assets In Queue Jobs

For local disks, `local_path` can be passed directly to processing libraries. For remote disks such as S3 or MinIO, `local_path` is `null`, so use `withTemporaryFile()`:

```php
$asset->withTemporaryFile(static function (string $source): void {
    service('image')
        ->withFile($source)
        ->fit(300, 300, 'center')
        ->save(WRITEPATH . 'cache/thumbnail.jpg');
});
```

To generate a variant on remote storage, write the processed output back through the variant:

```php
$variants->assetVariant('thumbnail', static function (AssetVariant $variant, Asset $asset): void {
    $asset->withTemporaryFile(static function (string $source) use ($variant): void {
        $target = tempnam(sys_get_temp_dir(), 'asset_variant_');
        if ($target === false) {
            throw new RuntimeException('Unable to create temporary variant file.');
        }

        try {
            service('image')
                ->withFile($source)
                ->fit(300, 300, 'center')
                ->save($target);

            $contents = file_get_contents($target);
            if ($contents === false) {
                throw new RuntimeException('Unable to read processed variant file.');
            }

            $variant->writeFile($contents);
        } finally {
            @unlink($target);
        }
    });
});
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

Use internal properties for backend metadata that should be persisted with the asset but not exposed as public custom properties:

```php
$asset->setInternalProperty('s3_etag', 'etag-value');
$asset->setInternalProperty('scan_status', 'clean');
$asset->save();
```

## Conclusion

The `Asset` class is a fundamental component of CodeIgniter Asset Connect that provides a rich set of methods for working with files associated with entities in your application. By using its methods, you can access file information, manage public custom properties and backend internal properties, generate URLs, and more.
