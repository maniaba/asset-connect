# Pending Asset Manager

The `PendingAssetManager` class provides a high-level API for managing pending assets. It handles storing, retrieving, and deleting pending assets, as well as managing their lifecycle and expiration.

## Overview

`PendingAssetManager` acts as a facade over the configured `PendingStorageInterface` implementation, providing a simple and consistent interface for working with pending assets.

**Namespace:** `Maniaba\AssetConnect\Pending\PendingAssetManager`

## Creating an Instance

```php
use Maniaba\AssetConnect\Pending\PendingAssetManager;

// Using default storage from configuration
$manager = PendingAssetManager::make();

// Using custom storage
$customStorage = new MyCustomPendingStorage();
$manager = PendingAssetManager::make($customStorage);
```

## Methods

### make()

Creates a new instance of `PendingAssetManager`.

```php
public static function make(?PendingStorageInterface $storage = null): PendingAssetManager
```

**Parameters:**
- `$storage` - Optional custom pending storage implementation. If null, uses the storage configured in `app/Config/Asset.php`

**Returns:** New `PendingAssetManager` instance

**Example:**
```php
// Default storage
$manager = PendingAssetManager::make();

// Custom storage
use App\Storage\S3PendingStorage;
$manager = PendingAssetManager::make(new S3PendingStorage());
```

### store()

Stores a pending asset. If the asset doesn't have an ID, generates a new one and stores both file and metadata. If the asset already has an ID, updates only the metadata.

```php
public function store(PendingAsset $pendingAsset, ?int $ttlSeconds = null): void
```

**Parameters:**
- `$pendingAsset` - The pending asset to store
- `$ttlSeconds` - Optional TTL in seconds (overrides default if provided)

**Throws:**
- `PendingAssetException` - If unable to store the asset
- `RandomException` - If unable to generate unique ID

**Examples:**

#### Store new pending asset
```php
use Maniaba\AssetConnect\Pending\PendingAsset;
use Maniaba\AssetConnect\Pending\PendingAssetManager;

$pending = PendingAsset::createFromFile('/path/to/photo.jpg');
$pending->usingName('Profile Photo');

$manager = PendingAssetManager::make();
$manager->store($pending);

// ID is now available
$pendingId = $pending->id;
```

#### Store with custom TTL
```php
$pending = PendingAsset::createFromFile('/path/to/document.pdf');

// Store with 1 hour TTL instead of default 24 hours
$manager = PendingAssetManager::make();
$manager->store($pending, 3600);
```

#### Update metadata only
```php
// Fetch existing pending asset
$manager = PendingAssetManager::make();
$pending = $manager->fetchById($existingId);

// Update metadata
$pending->withCustomProperty('status', 'reviewed');
$pending->usingName('Updated Name');

// Store - only updates metadata.json, file remains unchanged
$manager->store($pending);
```

### fetchById()

Fetches a pending asset by its ID. Returns `null` if not found or expired.

```php
public function fetchById(string $id): ?PendingAsset
```

**Parameters:**
- `$id` - Unique identifier of the pending asset

**Returns:** `PendingAsset` object if found and not expired, `null` otherwise

**Throws:**
- `PendingAssetException` - If unable to read metadata

**Example:**
```php
$manager = PendingAssetManager::make();

$pending = $manager->fetchById('a1b2c3d4e5f6');

if ($pending === null) {
    // Asset not found or expired
    echo "Pending asset not found";
} else {
    // Use the asset
    echo $pending->name;
    echo $pending->file_name;
}
```

**Automatic Expiration Handling:**

The method automatically checks if the asset has expired by comparing `created_at + ttl` with the current time. If expired:
1. Attempts to delete the expired asset
2. Returns `null`

```php
$manager = PendingAssetManager::make();
$pending = $manager->fetchById($id);

// If returned null, either:
// 1. ID doesn't exist
// 2. Asset has expired (and was automatically deleted)
if ($pending === null) {
    return response()->json(['error' => 'Asset not found or expired'], 404);
}
```

### deleteById()

Deletes a pending asset by its ID.

```php
public function deleteById(string $id): bool
```

**Parameters:**
- `$id` - Unique identifier of the pending asset to delete

**Returns:** `true` if deleted successfully, `false` otherwise

**Example:**
```php
$manager = PendingAssetManager::make();

$success = $manager->deleteById($pendingId);

if ($success) {
    echo "Asset deleted successfully";
} else {
    echo "Failed to delete asset or asset not found";
}
```

**Note:** When using `addAssetFromPending()`, pending assets are automatically cleaned up after successful addition to an entity.

### cleanExpiredPendingAssets()

Manually triggers cleanup of all expired pending assets.

```php
public function cleanExpiredPendingAssets(): void
```

**Example:**
```php
$manager = PendingAssetManager::make();
$manager->cleanExpiredPendingAssets();

echo "Expired assets cleaned";
```

**Automatic Cleanup:**

Expired pending assets are automatically cleaned up by the `AssetConnectJob` queue job. When assets are processed, the job also handles cleanup of expired pending assets from the default pending storage. This ensures that temporary files don't accumulate over time.

If you need to manually trigger cleanup outside of the queue job, you can use the method shown above.

## Complete Usage Examples

### Example 1: Simple Upload Flow

```php
// Upload endpoint
public function upload()
{
    $file = $this->request->getFile('photo');

    $pending = PendingAsset::createFromFile($file);
    $pending->usingName('User Photo');

    $manager = PendingAssetManager::make();
    $manager->store($pending);

    return $this->response->setJSON([
        'pending_id' => $pending->id
    ]);
}

// Confirm endpoint
public function confirm()
{
    $pendingId = $this->request->getPost('pending_id');

    $manager = PendingAssetManager::make();
    $pending = $manager->fetchById($pendingId);

    if (!$pending) {
        return $this->response->setStatusCode(404);
    }

    // Add to entity
    $user->addAssetFromPending($pending)
        ->toAssetCollection(Photos::class)
        ->save();

    // Clean up
    $manager->deleteById($pendingId);

    return $this->response->setJSON(['success' => true]);
}
```

### Example 2: Multi-step Upload with Metadata Editing

```php
// Step 1: Upload file
public function uploadFile()
{
    $file = $this->request->getFile('file');

    $pending = PendingAsset::createFromFile($file);

    $manager = PendingAssetManager::make();
    $manager->store($pending);

    return $this->response->setJSON([
        'pending_id' => $pending->id,
        'file_name' => $pending->file_name,
        'size' => $pending->size
    ]);
}

// Step 2: Edit metadata
public function updateMetadata()
{
    $pendingId = $this->request->getPost('pending_id');
    $name = $this->request->getPost('name');
    $alt = $this->request->getPost('alt');

    $manager = PendingAssetManager::make();
    $pending = $manager->fetchById($pendingId);

    if (!$pending) {
        return $this->response->setStatusCode(404);
    }

    // Update metadata (file remains unchanged)
    $pending->usingName($name)
        ->withCustomProperty('alt', $alt);

    $manager->store($pending);

    return $this->response->setJSON(['success' => true]);
}

// Step 3: Confirm and attach
public function confirmUpload()
{
    $pendingId = $this->request->getPost('pending_id');

    $manager = PendingAssetManager::make();
    $pending = $manager->fetchById($pendingId);

    if (!$pending) {
        return $this->response->setStatusCode(404);
    }

    $user->addAssetFromPending($pending)
        ->toAssetCollection(Images::class)
        ->save();


    return $this->response->setJSON(['success' => true]);
}
```

### Example 3: Batch Upload

```php
public function batchUpload()
{
    $files = $this->request->getFiles();
    $manager = PendingAssetManager::make();
    $pendingIds = [];

    foreach ($files['photos'] as $file) {
        if (!$file->isValid()) {
            continue;
        }

        $pending = PendingAsset::createFromFile($file);
        $manager->store($pending);

        $pendingIds[] = $pending->id;
    }

    return $this->response->setJSON([
        'pending_ids' => $pendingIds
    ]);
}

public function confirmBatch()
{
    $pendingIds = $this->request->getPost('pending_ids');
    $manager = PendingAssetManager::make();

    foreach ($pendingIds as $pendingId) {
        $pending = $manager->fetchById($pendingId);

        if (!$pending) {
            continue; // Skip expired or invalid
        }

        $product->addAssetFromPending($pending)
            ->toAssetCollection(ProductImages::class)
            ->save();
    }

    return $this->response->setJSON(['success' => true]);
}
```

## Error Handling

### Handling Expired Assets

```php
$manager = PendingAssetManager::make();
$pending = $manager->fetchById($pendingId);

if ($pending === null) {
    return $this->response->setStatusCode(410) // 410 Gone
        ->setJSON([
            'error' => 'This upload has expired. Please upload again.',
            'code' => 'ASSET_EXPIRED'
        ]);
}
```

### Handling Storage Errors

```php
try {
    $manager = PendingAssetManager::make();
    $manager->store($pending);
} catch (PendingAssetException $e) {
    log_message('error', 'Failed to store pending asset: ' . $e->getMessage());

    return $this->response->setStatusCode(500)
        ->setJSON([
            'error' => 'Failed to store file. Please try again.',
            'code' => 'STORAGE_ERROR'
        ]);
}
```

### Validation Before Storing

```php
$file = $this->request->getFile('photo');

// Validate before creating pending asset
if (!$file->isValid()) {
    throw new \RuntimeException('Invalid file upload');
}

if ($file->getSize() > 10 * 1024 * 1024) { // 10MB
    throw new \RuntimeException('File too large');
}

$allowedMimes = ['image/jpeg', 'image/png', 'image/gif'];
if (!in_array($file->getMimeType(), $allowedMimes)) {
    throw new \RuntimeException('Invalid file type');
}

// Now safe to create and store
$pending = PendingAsset::createFromFile($file);
$manager = PendingAssetManager::make();
$manager->store($pending);
```

## Best Practices

### 1. Pending Assets Are Auto-Cleaned

```php
// Pending assets are automatically cleaned up after successful addition
$user->addAssetFromPending($pendingId)
    ->toAssetCollection(Photos::class)
    ->save();
// File is automatically removed from pending storage
```

### 2. Check for Expiration

```php
// Good ✓
$pending = $manager->fetchById($id);
if (!$pending) {
    return response()->json(['error' => 'Expired'], 410);
}

// Bad ✗
$pending = $manager->fetchById($id);
$user->addAssetFromPending($pending)->save(); // May be null!
```

### 3. Use Appropriate TTL

```php
// Short-lived uploads (e.g., profile picture)
$manager->store($pending, 1800); // 30 minutes

// Long-lived uploads (e.g., document approval workflow)
$manager->store($pending, 86400 * 7); // 7 days
```

> **Note:** Expired pending assets are automatically cleaned up by the `AssetConnectJob` queue job when processing assets. No additional setup is required for automatic cleanup.

## Configuration

The default pending storage is configured in `app/Config/Asset.php`:

```php
use Maniaba\AssetConnect\Pending\DefaultPendingStorage;

class Asset extends BaseConfig
{
    public string $pendingStorage = DefaultPendingStorage::class;
}
```

To use a custom storage implementation:

```php
use App\Storage\CustomPendingStorage;

class Asset extends BaseConfig
{
    public string $pendingStorage = CustomPendingStorage::class;
}
```

## See Also

- [Pending Assets](pending.md) - Overview of pending assets functionality
- [DefaultPendingStorage](pending-storage.md) - Default filesystem storage implementation
- [Custom Pending Storage](custom-pending-storage.md) - Creating custom storage implementations

