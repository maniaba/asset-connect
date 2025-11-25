# Custom Pending Storage

This guide explains how to create and configure custom storage implementations for pending assets. By default, AssetConnect uses filesystem storage (`DefaultPendingStorage`), but you can implement your own storage backend for services like Amazon S3, Redis, or any other storage solution.

## Overview

Custom pending storage allows you to:

- Store pending assets in cloud storage (S3, Azure Blob, Google Cloud Storage)
- Use in-memory storage for testing or caching (Redis, Memcached)
- Implement custom TTL and cleanup logic
- Add custom security token providers
- Integrate with your existing storage infrastructure

## Implementing PendingStorageInterface

To create a custom pending storage, implement the `PendingStorageInterface`:

```php
use Maniaba\AssetConnect\Pending\Interfaces\PendingStorageInterface;
use Maniaba\AssetConnect\Pending\PendingAsset;
use Maniaba\AssetConnect\Pending\Interfaces\PendingSecurityTokenInterface;

class MyCustomPendingStorage implements PendingStorageInterface
{
    public function generatePendingId(): string
    {
        // Generate unique ID for pending asset
    }

    public function fetchById(string $id): ?PendingAsset
    {
        // Retrieve pending asset by ID
    }

    public function store(PendingAsset $asset, ?string $id = null): void
    {
        // Store pending asset
    }

    public function deleteById(string $id): bool
    {
        // Delete pending asset by ID
    }

    public function getDefaultTTLSeconds(): int
    {
        // Return default TTL in seconds
    }

    public function cleanExpiredPendingAssets(): void
    {
        // Remove all expired pending assets
    }

    public function pendingSecurityToken(): ?PendingSecurityTokenInterface
    {
        // Return security token provider (optional)
    }
}
```

## Required Methods

### generatePendingId()

Generates a unique identifier for a new pending asset.

```php
public function generatePendingId(): string
```

**Returns:** Unique string identifier

**Example:**
```php
public function generatePendingId(): string
{
    // Simple UUID
    return bin2hex(random_bytes(16));

    // Or with prefix
    return uniqid('pending_', true);

    // Or using UUID library
    return Uuid::uuid4()->toString();
}
```

### fetchById()

Retrieves a pending asset by its ID.

```php
public function fetchById(string $id): ?PendingAsset
```

**Parameters:**
- `$id` - Unique identifier of the pending asset

**Returns:** `PendingAsset` object or `null` if not found

**Example:**
```php
public function fetchById(string $id): ?PendingAsset
{
    $data = $this->storageClient->get($id);

    if (!$data) {
        return null;
    }

    // Reconstruct PendingAsset from stored data
    return PendingAsset::createFromFile($data['file_path'], $data['metadata']);
}
```

### store()

Stores a pending asset. Should handle both new creation and metadata updates.

```php
public function store(PendingAsset $asset, ?string $id = null): void
```

**Parameters:**
- `$asset` - The pending asset to store
- `$id` - Optional ID (if updating existing asset)

**Example:**
```php
public function store(PendingAsset $asset, ?string $id = null): void
{
    $id ??= $this->generatePendingId();
    $asset->setId($id);

    $data = [
        'id' => $id,
        'file' => $asset->file->getRealPath(),
        'metadata' => json_encode($asset),
        'created_at' => time(),
        'ttl' => $asset->ttl
    ];

    $this->storageClient->put($id, $data);
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
public function deleteById(string $id): bool
{
    return $this->storageClient->delete($id);
}
```

### getDefaultTTLSeconds()

Returns the default Time To Live for pending assets in seconds.

```php
public function getDefaultTTLSeconds(): int
```

**Returns:** TTL in seconds

**Example:**
```php
public function getDefaultTTLSeconds(): int
{
    // 1 hour
    return 3600;

    // 24 hours (default)
    return 86400;

    // 7 days
    return 604800;
}
```

### cleanExpiredPendingAssets()

Removes all expired pending assets.

```php
public function cleanExpiredPendingAssets(): void
```

**Example:**
```php
public function cleanExpiredPendingAssets(): void
{
    $allAssets = $this->storageClient->getAll();
    $now = time();

    foreach ($allAssets as $id => $asset) {
        $expiresAt = $asset['created_at'] + $asset['ttl'];

        if ($expiresAt < $now) {
            $this->deleteById($id);
        }
    }
}
```

### pendingSecurityToken()

Returns a security token provider for pending operations (optional).

```php
public function pendingSecurityToken(): ?PendingSecurityTokenInterface
```

**Returns:** Security token provider or `null`

**Example:**
```php
public function pendingSecurityToken(): ?PendingSecurityTokenInterface
{
    return new MyCustomSecurityToken();
}
```

## Storage Implementation Examples

You can implement custom storage for various backends based on your needs:

- **Cloud Storage**: Amazon S3, Azure Blob Storage, Google Cloud Storage
- **In-Memory Storage**: Redis, Memcached (useful for fast temporary storage with automatic TTL handling)
- **Database Storage**: MySQL, PostgreSQL (store metadata and file paths or binary data)
- **Distributed Storage**: Ceph, MinIO, or other object storage solutions

Each implementation should follow the `PendingStorageInterface` contract and handle file storage, metadata management, and expiration logic according to your specific infrastructure requirements.

## Configuring Custom Storage

After implementing your custom storage, configure it in `app/Config/Asset.php`:

```php
namespace Config;

use Maniaba\AssetConnect\Config\Asset as BaseConfig;
use App\Storage\S3PendingStorage;

class Asset extends BaseConfig
{
    public string $pendingStorage = S3PendingStorage::class;
}
```

## Using Custom Storage

Once configured, your custom storage will be used automatically:

```php
// Uses your custom storage automatically
$pending = PendingAsset::createFromFile('/path/to/file.jpg');
$pending->store();

// Also works with Pending Asset Manager
$manager = PendingAssetManager::make();
$pending = $manager->fetchById($id);
```

### Using Custom Storage Per Request

You can also use custom storage on a per-request basis:

```php
use App\Storage\S3PendingStorage;

$customStorage = new S3PendingStorage();

// With PendingAsset
$pending = PendingAsset::createFromFile('/path/to/file.jpg');
$pending->store($customStorage);

// With Pending Asset Manager
$manager = PendingAssetManager::make($customStorage);

// With addAssetFromPending
$user->addAssetFromPending($pendingId, $customStorage)
    ->toAssetCollection(Photos::class);
```

## Best Practices

### 1. Handle Expiration Properly

```php
public function fetchById(string $id): ?PendingAsset
{
    // Always check TTL before returning
    if ($this->isExpired($id)) {
        $this->deleteById($id);
        return null;
    }

    return $this->loadAsset($id);
}
```

### 2. Use Appropriate TTL

```php
public function getDefaultTTLSeconds(): int
{
    // Shorter TTL for memory storage
    if ($this->isMemoryStorage()) {
        return 3600; // 1 hour
    }

    // Longer TTL for persistent storage
    return 86400; // 24 hours
}
```

### 3. Implement Proper Error Handling

```php
public function store(PendingAsset $asset, ?string $id = null): void
{
    try {
        $this->doStore($asset, $id);
    } catch (\Exception $e) {
        log_message('error', 'Failed to store pending asset: ' . $e->getMessage());
        throw PendingAssetException::forUnableToStorePendingAsset(
            $id ?? 'unknown',
            $e->getMessage()
        );
    }
}
```

### 4. Cleanup Resources

```php
public function __destruct()
{
    // Close connections
    if ($this->connection) {
        $this->connection->close();
    }
}
```

### 5. Add Logging

```php
public function deleteById(string $id): bool
{
    $success = $this->performDelete($id);

    if ($success) {
        log_message('info', "Deleted pending asset: {$id}");
    } else {
        log_message('warning', "Failed to delete pending asset: {$id}");
    }

    return $success;
}
```


## Troubleshooting

### Storage Connection Issues

```php
public function __construct()
{
    try {
        $this->initializeStorage();
    } catch (\Exception $e) {
        log_message('critical', 'Failed to initialize pending storage: ' . $e->getMessage());
        throw new \RuntimeException('Pending storage initialization failed');
    }
}
```

### Large File Handling

```php
public function store(PendingAsset $asset, ?string $id = null): void
{
    $fileSize = $asset->size;
    $maxSize = 100 * 1024 * 1024; // 100MB

    if ($fileSize > $maxSize) {
        throw new PendingAssetException("File too large for pending storage: {$fileSize} bytes");
    }

    // Use streaming for large files
    $this->streamStore($asset, $id);
}
```

### Memory Management

```php
public function fetchById(string $id): ?PendingAsset
{
    // Don't load entire file into memory
    $metadata = $this->fetchMetadata($id);

    if (!$metadata) {
        return null;
    }

    // Stream file to temp location
    $tempFile = $this->streamToTemp($id);

    return PendingAsset::createFromFile($tempFile, $metadata);
}
```

## See Also

- [Pending Assets](pending.md) - Overview of pending assets functionality
- [Pending Asset Manager](pending-asset-manager.md) - Manager class documentation
- [DefaultPendingStorage](pending-storage.md) - Default filesystem implementation

