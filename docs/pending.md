# Pending Assets

Pending assets allow temporary storage of files and their metadata before final attachment to an entity. This functionality is especially useful for upload flows where a user first uploads a file, and later confirms where the asset will be added and with which settings.

## What are Pending Assets?

A pending asset represents a temporary file that is not yet permanently attached to an entity in your application. Each pending asset contains:

- The actual file stored on disk
- Metadata about the asset (name, custom properties, order, preserve_original)
- Expiration time (TTL - Time To Live)
- A unique ID for identification

Pending assets are stored in a temporary directory and automatically deleted after the TTL expires (default 24 hours).

## Where are Pending Assets Stored?

The default storage (`DefaultPendingStorage`) uses the filesystem to store pending assets. The directory structure is as follows:

```
WRITEPATH/assets_pending/
├── <pendingId>/
│   ├── file              # Raw file
│   └── metadata.json     # Metadata in JSON format
```

**Example:**
```
writable/assets_pending/
├── a1b2c3d4e5f6/
│   ├── file              # profile.jpg
│   └── metadata.json     # {"id":"a1b2c3d4e5f6","name":"Profile Photo",...}
```

**Default expiration time (TTL):** 86400 seconds (24 hours)

After an asset expires (`created_at + ttl < now`), `PendingAssetManager::fetchById()` will return `null` and attempt to automatically delete the expired asset.

## Main Classes

### PendingAsset

A class that represents a pending asset. Contains the file and metadata.

**Namespace:** `Maniaba\AssetConnect\Pending\PendingAsset`

**Properties:**

| Property | Type | Description |
|----------|------|-------------|
| `id` | string | Unique identifier for the pending asset |
| `name` | string | Display name of the asset |
| `file_name` | string | File name |
| `mime_type` | string | MIME type of the file |
| `size` | int | File size in bytes |
| `ttl` | int | Time to live in seconds |
| `created_at` | Time | Creation time |
| `updated_at` | Time | Last modification time |
| `order` | int | Asset order |
| `preserve_original` | bool | Whether to preserve the original file |
| `custom_properties` | array | Additional custom properties |
| `file` | File\|UploadedFile | Reference to the actual file |

### PendingAssetManager

Manager class for working with pending assets. Provides a high-level API for storing, fetching, and deleting.

**Namespace:** `Maniaba\AssetConnect\Pending\PendingAssetManager`

### DefaultPendingStorage

Default filesystem implementation for storing pending assets.

**Namespace:** `Maniaba\AssetConnect\Pending\DefaultPendingStorage`

## Creating Pending Assets

Pending assets are created using factory methods of the `PendingAsset` class.

### createFromFile

Creates a pending asset from a file.

```php
public static function createFromFile(
    File|string|UploadedFile $file,
    array|string $attributes = []
): PendingAsset
```

**Parameters:**
- `$file` - File (File object, path as string, or UploadedFile)
- `$attributes` - Optional attributes as array or JSON string

**Example:**
```php
use Maniaba\AssetConnect\Pending\PendingAsset;

// From path
$pending = PendingAsset::createFromFile('/path/to/photo.jpg');

// From File object
$file = new \CodeIgniter\Files\File('/path/to/document.pdf');
$pending = PendingAsset::createFromFile($file);

// From UploadedFile (from form)
$uploadedFile = $this->request->getFile('avatar');
$pending = PendingAsset::createFromFile($uploadedFile);

// With attributes
$pending = PendingAsset::createFromFile('/path/to/photo.jpg', [
    'name' => 'Profile Photo',
    'order' => 1,
    'custom_properties' => ['alt' => 'Photo description']
]);
```

### createFromString

Creates a pending asset from a string.

```php
public static function createFromString(
    string $string,
    array|string $attributes = []
): PendingAsset
```

**Example:**
```php
$content = file_get_contents('http://example.com/image.jpg');
$pending = PendingAsset::createFromString($content, [
    'file_name' => 'downloaded-image.jpg'
]);
```

### createFromBase64

Creates a pending asset from a base64 encoded string.

```php
public static function createFromBase64(
    string $base64data,
    array|string $attributes = []
): PendingAsset
```

**Example:**
```php
// Base64 string from JavaScript
$base64 = $_POST['image_data']; // "data:image/png;base64,iVBORw0KG..."

// Remove data URI prefix if present
$base64 = preg_replace('/^data:image\/\w+;base64,/', '', $base64);

$pending = PendingAsset::createFromBase64($base64, [
    'file_name' => 'screenshot.png',
    'name' => 'Screenshot'
]);
```

## Setting Metadata

Pending asset supports a fluent interface for setting metadata:

```php
$pending = PendingAsset::createFromFile('/path/to/photo.jpg');

$pending->usingName('Profile Picture')
    ->usingFileName('profile.jpg')
    ->setOrder(1)
    ->preservingOriginal(true)
    ->withCustomProperty('alt', 'Image description')
    ->withCustomProperty('caption', 'My profile picture')
    ->withCustomProperties([
        'photographer' => 'John Doe',
        'location' => 'Zagreb'
    ]);
```

### Available Methods

| Method | Description |
|--------|-------------|
| `usingName(string $name)` | Sets the display name of the asset |
| `usingFileName(string $fileName)` | Sets the file name |
| `preservingOriginal(bool $preserve = true)` | Sets whether to preserve the original |
| `setOrder(int $order)` | Sets the order |
| `withCustomProperty(string $key, mixed $value)` | Adds a single custom property |
| `withCustomProperties(array $properties)` | Sets all custom properties |
| `setId(string $id)` | Sets the ID (usually internal) |
| `setTTL(int $ttl)` | Sets the time to live in seconds |

## Storing and Retrieving

### Storing a pending asset

```php
use Maniaba\AssetConnect\Pending\PendingAssetManager;

$manager = PendingAssetManager::make();

// Create pending asset
$pending = PendingAsset::createFromFile('/path/to/photo.jpg');
$pending->usingName('My Photo');

// Store (automatically generates ID)
$manager->store($pending);

// ID is now available
$pendingId = $pending->id; // e.g., "a1b2c3d4e5f6789012345678"

// Return ID to client/front-end
return $this->response->setJSON(['pending_id' => $pendingId]);
```

### Retrieving a pending asset

```php
$manager = PendingAssetManager::make();

$pendingId = $this->request->getPost('pending_id');
$pending = $manager->fetchById($pendingId);

if ($pending === null) {
    // Asset not found or expired
    return $this->response->setStatusCode(404)
        ->setJSON(['error' => 'Pending asset not found or expired']);
}

// Use pending asset
echo $pending->name;
echo $pending->file_name;
echo $pending->getCustomProperty('alt');
```

### Deleting a pending asset

```php
$manager = PendingAssetManager::make();

$success = $manager->deleteById($pendingId);

if ($success) {
    echo "Asset has been deleted";
}
```

### Cleaning expired assets

```php
// Delete all expired pending assets
$manager = PendingAssetManager::make();
$manager->cleanExpiredPendingAssets();
```

**Note:** You can set up a cron job or scheduled task to periodically call this method.

## Create vs Update (Important!)

One of the key characteristics of pending storage is the distinction between creating a new asset and updating metadata of an existing asset.

### Create (new creation)

When a pending asset **does not have** an ID, `store()` will:
1. Generate a new unique ID
2. Store the raw file in `WRITEPATH/assets_pending/<id>/file`
3. Store metadata in `WRITEPATH/assets_pending/<id>/metadata.json`

```php
$manager = PendingAssetManager::make();

$pending = PendingAsset::createFromFile('/path/to/photo.jpg');
// $pending->id is an empty string ""

$manager->store($pending);
// Now $pending->id has a value like "a1b2c3d4e5f6"
```

### Update (metadata-only update)

When a pending asset **has** an ID, `store()` will:
1. Update only `metadata.json`
2. **WILL NOT** overwrite the existing `file` on disk

```php
$manager = PendingAssetManager::make();

// Fetch existing pending asset
$pending = $manager->fetchById('a1b2c3d4e5f6');

// Change metadata
$pending->withCustomProperty('caption', 'New caption');
$pending->usingName('Changed name');

// Store - updates only metadata.json
$manager->store($pending);

// Raw file remains identical!
```

**Important note:** If you want to **replace the file** for an existing pending ID:
1. First delete the old pending asset: `$manager->deleteById($id)`
2. Create a new pending asset and store it: `$manager->store($newPending)`

## Adding Pending Assets to an Entity: addAssetFromPending

The `addAssetFromPending` method allows easy conversion of a pending asset into a real asset attached to an entity.

### Method Signature

```php
public function addAssetFromPending(
    PendingAsset|string $pendingAsset,
    ?PendingStorageInterface $storage = null
): AssetAdder
```

**Parameters:**
- `$pendingAsset` - PendingAsset object or pending asset ID (string)
- `$storage` - Optional custom pending storage (null uses default)

**Returns:** `AssetAdder` object for further configuration and saving

### How does it work?

The `addAssetFromPending` method:

1. If you pass a string (ID), fetches the `PendingAsset` using `PendingAssetManager::fetchById()`
2. If the pending asset is not found or has expired, throws `AssetException::forPendingAssetNotFound()`
3. Creates an `AssetAdder` using the actual file from the pending asset (`$pendingAsset->file`)
4. Automatically transfers all metadata from the pending asset:
   - `usingName()` - display name
   - `usingFileName()` - file name
   - `setOrder()` - order
   - `withCustomProperties()` - all custom properties
   - `preservingOriginal()` - preserve original
5. Returns the `AssetAdder` which you can further configure (e.g., set collection) and save

**Important:** The method **DOES NOT automatically delete** the pending asset after adding. You must manually call `PendingAssetManager::deleteById()` if you want to clean up pending storage.

### Usage Examples

#### 1. Basic adding with ID

```php
// In controller after user confirms upload
public function confirmUpload()
{
    $pendingId = $this->request->getPost('pending_id');

    // Get user
    $user = model(UserModel::class)->find($userId);

    // Add asset from pending
    $user->addAssetFromPending($pendingId)
        ->toCollection(ProfilePhotos::class)
        ->save();

    // Delete pending asset after successful addition
    PendingAssetManager::make()->deleteById($pendingId);

    return $this->response->setJSON(['success' => true]);
}
```

#### 2. Adding with PendingAsset object

```php
$manager = PendingAssetManager::make();
$pending = $manager->fetchById($pendingId);

if (!$pending) {
    throw new \RuntimeException('Pending asset not found');
}

// Check something before adding
if ($pending->size > 5 * 1024 * 1024) {
    throw new \RuntimeException('File is too large');
}

// Add asset
$user->addAssetFromPending($pending)
    ->toCollection(Documents::class)
    ->save();

// Clean up pending
$manager->deleteById($pendingId);
```

#### 3. Additional configuration before saving

```php
$assetAdder = $user->addAssetFromPending($pendingId);

// Override some properties from pending
$assetAdder->usingName('New Title')
    ->withCustomProperty('verified', true)
    ->toCollection(Images::class);

// Save
$assetAdder->save();

// Delete pending
PendingAssetManager::make()->deleteById($pendingId);
```

#### 4. Working with multiple pending assets

```php
$pendingIds = $this->request->getPost('pending_ids'); // ['id1', 'id2', 'id3']

$manager = PendingAssetManager::make();

foreach ($pendingIds as $pendingId) {
    try {
        $product->addAssetFromPending($pendingId)
            ->toCollection(ProductImages::class)
            ->save();

        // Delete after successful addition
        $manager->deleteById($pendingId);
    } catch (\Exception $e) {
        log_message('error', 'Failed to add pending asset: ' . $e->getMessage());
    }
}
```

#### 5. Custom pending storage

```php
use App\CustomPendingStorage;

$customStorage = new CustomPendingStorage();

$user->addAssetFromPending($pendingId, $customStorage)
    ->toCollection(Avatars::class)
    ->save();
```

### Complete Example: Upload Flow with Front-end

#### Backend: Upload endpoint

```php
// app/Controllers/Upload.php
public function uploadPending()
{
    $file = $this->request->getFile('file');

    if (!$file->isValid()) {
        return $this->response->setStatusCode(400)
            ->setJSON(['error' => 'Invalid file']);
    }

    // Create pending asset
    $pending = PendingAsset::createFromFile($file);
    $pending->usingName($this->request->getPost('name') ?? $file->getClientName())
        ->withCustomProperty('user_id', auth()->id());

    // Store in pending storage
    $manager = PendingAssetManager::make();
    $manager->store($pending);

    // Return ID to client
    return $this->response->setJSON([
        'success' => true,
        'pending_id' => $pending->id,
        'file_name' => $pending->file_name,
        'size' => $pending->size
    ]);
}
```

#### Backend: Confirm endpoint

```php
// app/Controllers/Upload.php
public function confirmPending()
{
    $pendingId = $this->request->getPost('pending_id');
    $userId = auth()->id();

    $user = model(UserModel::class)->find($userId);

    try {
        // Add asset from pending
        $user->addAssetFromPending($pendingId)
            ->toCollection(ProfilePhotos::class)
            ->withCustomProperty('confirmed_at', date('Y-m-d H:i:s'))
            ->save();

        // Delete pending after success
        PendingAssetManager::make()->deleteById($pendingId);

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Asset added successfully'
        ]);
    } catch (\Exception $e) {
        return $this->response->setStatusCode(400)
            ->setJSON([
                'success' => false,
                'error' => $e->getMessage()
            ]);
    }
}
```

#### Frontend: JavaScript code

```javascript
// Upload file
async function uploadFile(file) {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('name', file.name);

    const response = await fetch('/upload/pending', {
        method: 'POST',
        body: formData
    });

    const data = await response.json();

    if (data.success) {
        console.log('Pending ID:', data.pending_id);
        return data.pending_id;
    }
}

// Confirm upload
async function confirmUpload(pendingId) {
    const response = await fetch('/upload/confirm-pending', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({pending_id: pendingId})
    });

    const data = await response.json();
    return data.success;
}

// Usage
document.getElementById('file-input').addEventListener('change', async (e) => {
    const file = e.target.files[0];
    const pendingId = await uploadFile(file);

    // User can review, edit metadata, etc.
    // Then confirm upload
    if (confirm('Add this file?')) {
        await confirmUpload(pendingId);
        alert('File added successfully!');
    }
});
```

## Updating Metadata Without Replacing the File

An important characteristic of pending storage is the ability to update metadata without overwriting the file.

### Example: Editing metadata

```php
// User first uploads file
$pending = PendingAsset::createFromFile($_FILES['photo']['tmp_name']);
$manager = PendingAssetManager::make();
$manager->store($pending);

$pendingId = $pending->id;
// Return ID to front-end

// Later user edits metadata (e.g., adds alt text, caption)
$pending = $manager->fetchById($pendingId);
$pending->withCustomProperty('alt', 'Beautiful sunset')
    ->withCustomProperty('caption', 'Sunset in Croatia')
    ->usingName('Sunset Photo');

// Save - only metadata.json is updated, file remains the same
$manager->store($pending);
```

### PHPUnit test: Verify that file remains unchanged

```php
public function testUpdatingPendingMetadataDoesNotOverwriteFile()
{
    $manager = PendingAssetManager::make();

    // Create and store pending asset
    $originalContent = 'original file contents';
    $pending = PendingAsset::createFromString($originalContent);
    $pending->withCustomProperty('version', 1);

    $manager->store($pending);
    $id = $pending->id;

    // Check file checksum
    $filePath = WRITEPATH . 'assets_pending' . DIRECTORY_SEPARATOR . $id . DIRECTORY_SEPARATOR . 'file';
    $this->assertFileExists($filePath);
    $checksumBefore = md5_file($filePath);

    // Update only metadata
    $pending->withCustomProperty('version', 2);
    $pending->usingName('Updated name');
    $manager->store($pending);

    // Re-fetch and verify metadata
    $reloaded = $manager->fetchById($id);
    $this->assertNotNull($reloaded);
    $this->assertEquals(2, $reloaded->custom_properties['version']);
    $this->assertEquals('Updated name', $reloaded->name);

    // Verify file is identical
    $checksumAfter = md5_file($filePath);
    $this->assertEquals($checksumBefore, $checksumAfter);

    // Verify content is actually the same
    $this->assertEquals($originalContent, file_get_contents($filePath));
}
```

## Advanced Techniques

### Custom Pending Storage

You can implement your own storage (e.g., S3, Redis) by implementing `PendingStorageInterface`:

```php
use Maniaba\AssetConnect\Pending\Interfaces\PendingStorageInterface;

class S3PendingStorage implements PendingStorageInterface
{
    public function generatePendingId(): string
    {
        return uniqid('s3_', true);
    }

    public function fetchById(string $id): ?PendingAsset
    {
        // Fetch from S3
    }

    public function store(PendingAsset $asset, ?string $id = null): void
    {
        // Store to S3
    }

    public function deleteById(string $id): bool
    {
        // Delete from S3
    }

    public function getDefaultTTLSeconds(): int
    {
        return 3600; // 1 hour
    }

    public function cleanExpiredPendingAssets(): void
    {
        // Clean expired from S3
    }

    public function pendingSecurityToken(): ?PendingSecurityTokenInterface
    {
        return new MyS3SecurityToken();
    }
}
```

### Configuring custom storage

In `app/Config/Asset.php`:

```php
use App\CustomPendingStorage;

class Asset extends BaseConfig
{
    public string $pendingStorage = CustomPendingStorage::class;
}
```

### Automatic cleanup of expired assets

Add to cron or scheduled task:

```php
// app/Commands/CleanPendingAssets.php
namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use Maniaba\AssetConnect\Pending\PendingAssetManager;

class CleanPendingAssets extends BaseCommand
{
    protected $group = 'Maintenance';
    protected $name = 'pending:clean';
    protected $description = 'Clean expired pending assets';

    public function run(array $params)
    {
        $manager = PendingAssetManager::make();
        $manager->cleanExpiredPendingAssets();

        $this->write('Expired pending assets cleaned successfully.', 'green');
    }
}
```

Run with: `php spark pending:clean`

### Validation before adding

```php
$manager = PendingAssetManager::make();
$pending = $manager->fetchById($pendingId);

if (!$pending) {
    throw new \RuntimeException('Pending asset not found');
}

// MIME type validation
$allowedMimes = ['image/jpeg', 'image/png', 'image/gif'];
if (!in_array($pending->mime_type, $allowedMimes)) {
    throw new \RuntimeException('Invalid file type');
}

// Size validation
if ($pending->size > 5 * 1024 * 1024) { // 5MB
    throw new \RuntimeException('File too large');
}

// Add to entity
$user->addAssetFromPending($pending)
    ->toCollection(Avatars::class)
    ->save();

// Delete pending
$manager->deleteById($pendingId);
```

## Troubleshooting

### `fetchById()` returns `null`

**Possible causes:**

1. **Asset has expired** - check if `created_at + ttl < now`
2. **ID doesn't exist** - check if directory `WRITEPATH/assets_pending/<id>` was created
3. **Corrupted metadata** - check if `metadata.json` is valid JSON
4. **Missing file** - check if `file` exists in the directory

**Verification:**
```php
$id = 'a1b2c3d4e5f6';
$basePath = WRITEPATH . 'assets_pending' . DIRECTORY_SEPARATOR . $id . DIRECTORY_SEPARATOR;

if (!is_dir($basePath)) {
    echo "Directory does not exist";
} elseif (!file_exists($basePath . 'file')) {
    echo "File does not exist";
} elseif (!file_exists($basePath . 'metadata.json')) {
    echo "Metadata does not exist";
} else {
    $metadata = json_decode(file_get_contents($basePath . 'metadata.json'), true);
    print_r($metadata);
}
```

### `AssetException::forPendingAssetNotFound()`

This error is thrown when:
- `addAssetFromPending()` receives an ID that doesn't exist or has expired

**Solution:**
```php
try {
    $user->addAssetFromPending($pendingId)
        ->toCollection(Images::class)
        ->save();
} catch (AssetException $e) {
    // Notify user that asset has expired
    return $this->response->setStatusCode(404)
        ->setJSON(['error' => 'Pending asset not found or expired. Please upload again.']);
}
```

### Disk space issues

Pending assets take up space. Ensure regular cleanup:

```php
// Run daily in cron
$manager = PendingAssetManager::make();
$manager->cleanExpiredPendingAssets();
```

### Permissions issues

Ensure `WRITEPATH/assets_pending/` has correct permissions:

```bash
chmod -R 755 writable/assets_pending
chown -R www-data:www-data writable/assets_pending
```

## Summary

- **Pending assets** enable temporary storage of files before final attachment to an entity
- **TTL** (default 24h) automatically deletes old pending assets
- **Create vs Update**: if pending has an ID, `store()` updates only metadata without overwriting the file
- **`addAssetFromPending()`** converts a pending asset into a real asset - simple and fast
- **Don't forget** to delete the pending asset after successful addition using `deleteById()`
- **Set up a cron job** for automatic cleanup of expired assets

Pending assets make upload processes more flexible and allow users to first upload a file, then edit metadata, and finally confirm where the asset will be added.

