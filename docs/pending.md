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
| `security_token` | string\|null | Short-lived security token assigned to the pending asset (set by PendingAssetManager when a token provider is configured) |


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
| `store(?PendingStorageInterface $storage = null, ?int $ttlSeconds = null)` | Stores the pending asset. If ID exists, updates metadata only; if no ID, creates new storage. Optional custom storage and TTL parameters. |
| `setSecurityToken(?string $token)` | Sets the security token on the `PendingAsset` (used internally by `PendingAssetManager`/storage after token generation) |

#### Creation Methods (Static)

| Method | Description |
|--------|-------------|
| `createFromFile(File\|string\|UploadedFile $file, array\|string $attributes = [])` | Creates a pending asset from a file object, file path, or uploaded file |
| `createFromBase64(string $base64data, array\|string $attributes = [])` | Creates a pending asset from base64 encoded string |
| `createFromString(string $string, array\|string $attributes = [])` | Creates a pending asset from string content (e.g., downloaded file content) |
| `createFromRequest(string ...$keyNames)` | Creates pending assets from HTTP request uploaded files (returns array grouped by field name) |


### Pending Asset Manager

Manager class for working with pending assets. Provides a high-level API for storing, fetching, and deleting.

**Namespace:** `Maniaba\AssetConnect\Pending\PendingAssetManager`

For detailed documentation, see [Pending Asset Manager](pending-asset-manager.md).

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

### createFromRequest

Creates pending assets directly from HTTP request uploaded files. This is the recommended method for handling file uploads from forms.

```php
public static function createFromRequest(string ...$keyNames): array
```

**Parameters:**
- `...$keyNames` - One or more field names from the request to process

**Returns:** Array of pending assets grouped by field name: `['fieldName' => [PendingAsset, ...]]`

**Throws:**

- `InvalidArgumentException` if no key names are provided
- `FileException` if uploaded file is invalid

**Features:**

- Automatically handles both single and multiple file uploads
- Only processes specified field names (ignores other files)
- Validates uploaded files before creating pending assets
- Returns empty array if specified fields don't exist or contain no files

**Example - Single file upload:**
```php
use Maniaba\AssetConnect\Pending\PendingAsset;

// HTML: <input type="file" name="avatar">

// Process uploaded file
$result = PendingAsset::createFromRequest('avatar');

if (!empty($result['avatar'])) {
    $pending = $result['avatar'][0]; // Get first (and only) file

    $pending->usingName('Profile Photo')
        ->toAssetCollection(ProfilePhotos::class)
        ->store();

    return $this->response->setJSON(['pending_id' => $pending->id]);
}
```

**Example - Multiple files in one field:**
```php
// HTML: <input type="file" name="documents[]" multiple>

// Process all uploaded documents
$result = PendingAsset::createFromRequest('documents');

foreach ($result['documents'] as $pending) {
    $pending->setOrder($index++)
        ->withCustomProperty('uploaded_by', auth()->id())
        ->store();

    $pendingIds[] = $pending->id;
}

return $this->response->setJSON(['pending_ids' => $pendingIds]);
```

**Example - Multiple different fields:**
```php
// HTML:
// <input type="file" name="avatar">
// <input type="file" name="cover">
// <input type="file" name="documents[]" multiple>

// Process only avatar and cover, ignore documents
$result = PendingAsset::createFromRequest('avatar', 'cover');

if (!empty($result['avatar'])) {
    $avatarPending = $result['avatar'][0];
    $avatarPending->usingName('User Avatar')->store();
}

if (!empty($result['cover'])) {
    $coverPending = $result['cover'][0];
    $coverPending->usingName('Cover Photo')->store();
}
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

## Storing Pending Assets

The `store()` method is used to save pending assets. It automatically handles both creating new pending assets and updating metadata of existing ones.

### Basic Store Example

```php
use Maniaba\AssetConnect\Pending\PendingAsset;

// Create pending asset
$pending = PendingAsset::createFromFile('/path/to/photo.jpg');
$pending->usingName('My Photo');

// Store (automatically generates ID)
$pending->store();

// ID is now available
$pendingId = $pending->id; // e.g., "a1b2c3d4e5f6789012345678"

// Return ID to client/front-end
return $this->response->setJSON(['pending_id' => $pendingId]);
```

### Store with Custom TTL

```php
$pending = PendingAsset::createFromFile('/path/to/document.pdf');

// Store with 1 hour TTL instead of default 24 hours
$pending->store(null, 3600);

echo "Pending ID: " . $pending->id;
```

### Update Metadata (Store with Existing ID)

```php
use Maniaba\AssetConnect\Pending\PendingAssetManager;

// Fetch existing pending asset
$pending = PendingAssetManager::make()->fetchById($existingId);

if ($pending) {
    // Update metadata
    $pending->withCustomProperty('status', 'reviewed');
    $pending->usingName('Updated Name');

    // Store - only updates metadata.json, file remains unchanged
    $pending->store();
}
```

**Note:** For complete `PendingAssetManager` API documentation (fetching, deleting, cleaning), see [Pending Asset Manager](pending-asset-manager.md).

## Create vs Update (Important!)

One of the key characteristics of pending storage is the distinction between creating a new asset and updating metadata of an existing asset.

### Create (new creation)

When a pending asset **does not have** an ID, `store()` will:
1. Generate a new unique ID
2. Store the raw file in `WRITEPATH/assets_pending/<id>/file`
3. Store metadata in `WRITEPATH/assets_pending/<id>/metadata.json`

```php
$pending = PendingAsset::createFromFile('/path/to/photo.jpg');
// $pending->id is an empty string ""

$pending->store();
// Now $pending->id has a value like "a1b2c3d4e5f6"
```

### Update (metadata-only update)

When a pending asset **has** an ID, `store()` will:
1. Update only `metadata.json`
2. **WILL NOT** overwrite the existing `file` on disk

```php
// Fetch existing pending asset
$pending = PendingAssetManager::make()->fetchById('a1b2c3d4e5f6');

// Change metadata
$pending->withCustomProperty('caption', 'New caption');
$pending->usingName('Changed name');

// Store - updates only metadata.json
$pending->store();

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

**Note:** Pending assets are automatically cleaned up from storage after being successfully added to an entity.

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
        ->toAssetCollection(ProfilePhotos::class);


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
    ->toAssetCollection(Documents::class);
```

#### 3. Additional configuration before saving

```php
$assetAdder = $user->addAssetFromPending($pendingId);

// Override some properties from pending
$assetAdder->usingName('New Title')
    ->withCustomProperty('verified', true)
    ->toAssetCollection(Images::class);

```

#### 4. Working with multiple pending assets

```php
$pendingIds = $this->request->getPost('pending_ids'); // ['id1', 'id2', 'id3']

$manager = PendingAssetManager::make();

foreach ($pendingIds as $pendingId) {
    try {
        $product->addAssetFromPending($pendingId)
            ->toAssetCollection(ProductImages::class);
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
    ->toAssetCollection(Avatars::class);
```

### Complete Example: Upload Flow with Front-end

#### Backend: Upload endpoint

```php
// app/Controllers/Upload.php
public function uploadPending()
{
    try {
        // Create pending asset from request using 'file' field
        $result = PendingAsset::createFromRequest('file');

        if (empty($result['file'])) {
            return $this->response->setStatusCode(400)
                ->setJSON(['error' => 'No file uploaded']);
        }

        $pending = $result['file'][0];

        // Set additional metadata
        $pending->usingName($this->request->getPost('name') ?? $pending->file_name)
            ->withCustomProperty('user_id', auth()->id());

        // Store in pending storage
        $pending->store();

        // Return ID to client
        return $this->response->setJSON([
            'success' => true,
            'pending_id' => $pending->id,
            'file_name' => $pending->file_name,
            'size' => $pending->size
        ]);

    } catch (\Exception $e) {
        return $this->response->setStatusCode(400)
            ->setJSON(['error' => $e->getMessage()]);
    }
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
            ->withCustomProperty('confirmed_at', date('Y-m-d H:i:s'))
            ->toAssetCollection(ProfilePhotos::class);


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
$pending->store();

$pendingId = $pending->id;
// Return ID to front-end

// Later user edits metadata (e.g., adds alt text, caption)
$pending = PendingAssetManager::make()->fetchById($pendingId);
$pending->withCustomProperty('alt', 'Beautiful sunset')
    ->withCustomProperty('caption', 'Sunset in Croatia')
    ->usingName('Sunset Photo');

// Save - only metadata.json is updated, file remains the same
$pending->store();
```

### PHPUnit test: Verify that file remains unchanged

```php
public function testUpdatingPendingMetadataDoesNotOverwriteFile()
{
    // Create and store pending asset
    $originalContent = 'original file contents';
    $pending = PendingAsset::createFromString($originalContent);
    $pending->withCustomProperty('version', 1);

    $pending->store();
    $id = $pending->id;

    // Check file checksum
    $filePath = WRITEPATH . 'assets_pending' . DIRECTORY_SEPARATOR . $id . DIRECTORY_SEPARATOR . 'file';
    $this->assertFileExists($filePath);
    $checksumBefore = md5_file($filePath);

    // Update only metadata
    $pending->withCustomProperty('version', 2);
    $pending->usingName('Updated name');
    $pending->store();

    // Re-fetch and verify metadata
    $reloaded = PendingAssetManager::make()->fetchById($id);
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

## Advanced Usage

For advanced topics including custom storage implementations (S3, Redis, etc.), see:

- [Custom Pending Storage](custom-pending-storage.md) - Implementing custom storage backends

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
        ->toAssetCollection(Images::class);
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
- **Automatic cleanup**: pending assets are automatically removed from storage after successful addition to an entity
- **Expired assets cleanup**: expired pending assets are automatically cleaned up by the `AssetConnectJob` queue job when processing assets

Pending assets make upload processes more flexible and allow users to first upload a file, then edit metadata, and finally confirm where the asset will be added.

## Security Tokens

Pending assets support security tokens to verify and control access to pending assets.

### What are Security Tokens?

Security tokens are short-lived tokens that ensure only authorized actions are performed on pending assets. They prevent misuse of pending asset IDs and allow validating requests from the client.

### Usage pattern

1. Token generation is automatic when you store a pending asset via `PendingAsset::store()` (which delegates to `PendingAssetManager::store()`). The manager will:

   - Generate a pending ID (when creating a new pending asset)
   - Persist the pending file/metadata using the configured pending storage
   - Invoke the configured token provider's `generateToken($pendingId)` and set the resulting token on the `PendingAsset` instance (`$pending->security_token`).

   Therefore you do NOT need to instantiate the token provider or call `generateToken()` manually in typical flows. Example:

```php
use Maniaba\AssetConnect\Pending\PendingAsset;

$result = PendingAsset::createFromRequest('file');
$pending = $result['file'][0];

// Persist pending asset - the PendingAssetManager will generate and persist the token
$pending->store();

// After storing, the token is available on the PendingAsset object (if a token provider is configured)
return $this->response->setJSON([
    'pending_id' => $pending->id,
    'security_token' => $pending->security_token,
]);
```

2. Token validation / retrieval behavior

   - `PendingSecurityTokenInterface::validateToken(PendingAsset $pendingAsset, ?string $tokenProvided = null): bool` validates the provided token against the pending asset's stored `security_token`.
   - If you don't pass a `$tokenProvided`, most built-in token providers (the abstract implementation) will automatically call `retrieveToken($pendingId)` and validate the retrieved value. For example, `SessionPendingSecurityToken::retrieveToken()` reads the token from session tempdata.

   Example — validating a token that the client passed explicitly:

```php
use Maniaba\AssetConnect\Pending\PendingAssetManager;
use Maniaba\AssetConnect\Pending\PendingSecurityToken\SessionPendingSecurityToken;

$manager = PendingAssetManager::make();
$pending = $manager->fetchById($pendingId);

if (! $pending) {
    throw new \RuntimeException('Pending asset not found');
}

// Validate by passing the token explicitly into fetchById().
// If token is invalid the method returns null.
$pending = $manager->fetchById($pendingId, $providedTokenFromClient);

if ($pending === null) {
    // Either asset not found, expired, or invalid token
    throw new \RuntimeException('Pending asset not found or invalid security token.');
}

// proceed to convert pending asset into a real asset
```

   Example — let the provider retrieve the token itself (no explicit token passed):

```php
$pending = $manager->fetchById($pendingId);

if ($pending === null) {
    // Asset not found, expired, or provider failed to validate token
    throw new \RuntimeException('Pending asset not found or invalid/expired token.');
}

// proceed to convert pending asset into a real asset
```

Notes:

- Token validation is executed inside `PendingAssetManager::fetchById(string $id, ?string $token = null): ?PendingAsset`. If the token provider is configured, `fetchById()` will call the provider's `validateToken()` internally. When validation fails `fetchById()` returns `null`.
- You can explicitly pass the token into `fetchById()` (useful when the client sends token in the request body). If you omit the token, the configured provider will usually attempt `retrieveToken($pendingId)` itself (for example, `SessionPendingSecurityToken` reads session tempdata).
- If `Config\Asset::$pendingSecurityToken` is `null`, token generation and validation are disabled and `fetchById()` will behave like a normal read (subject to expiration checks).
