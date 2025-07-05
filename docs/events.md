# Events

CodeIgniter Asset Connect fires several events that you can listen for in your application. These events allow you to perform custom actions when assets are created, updated, deleted, or when variants are created.

## Available Events

Asset Connect fires the following events:

| Event Name | Description | Event Object |
|------------|-------------|--------------|
| `asset.created` | Fired when an asset is created | `AssetCreated` |
| `asset.updated` | Fired when an asset is updated | `AssetUpdated` |
| `asset.deleted` | Fired when an asset is deleted | `AssetDeleted` |
| `variant.created` | Fired when a variant is created | `VariantCreated` |

## Listening for Events

You can listen for these events in your application's `app/Config/Events.php` file:

```php
// In app/Config/Events.php

use CodeIgniter\Events\Events;
use Maniaba\AssetConnect\Events\AssetCreated;
use Maniaba\AssetConnect\Events\AssetUpdated;
use Maniaba\AssetConnect\Events\AssetDeleted;
use Maniaba\AssetConnect\Events\VariantCreated;

// Listen for asset.created event
Events::on('asset.created', function (AssetCreated $event) {
    $asset = $event->getAsset();

    // Do something with the asset
    log_message('info', 'Asset created: ' . $asset->id);
});

// Listen for asset.updated event
Events::on('asset.updated', function (AssetUpdated $event) {
    $asset = $event->getAsset();

    // Do something with the updated asset
    log_message('info', 'Asset updated: ' . $asset->id);
});

// Listen for asset.deleted event
Events::on('asset.deleted', function (AssetDeleted $event) {
    $asset = $event->getAsset();

    // Do something with the asset
    log_message('info', 'Asset deleted: ' . $asset->id);
});

// Listen for variant.created event
Events::on('variant.created', function (VariantCreated $event) {
    $asset = $event->getAsset();
    $variant = $event->getVariant();
    $variantName = $variant->name;

    // Do something with the asset and variant name
    log_message('info', 'Variant created: ' . $variantName . ' for asset ' . $asset->id);
});
```

## Event Objects

Each event provides an event object that contains relevant information about the event:

### AssetCreated

The `AssetCreated` event object provides access to the newly created asset:

```php
Events::on('asset.created', function (AssetCreated $event) {
    $asset = $event->getAsset();

    // Access asset properties
    $assetId = $asset->id;
    $fileName = $asset->file_name;
    $mimeType = $asset->mime_type;
    $size = $asset->size;

    // Access custom properties
    $customProperties = $asset->getCustomProperties();

    // Perform custom actions
    // For example, send a notification, update a database record, etc.
});
```

### AssetUpdated

The `AssetUpdated` event object provides access to the updated asset:

```php
Events::on('asset.updated', function (AssetUpdated $event) {
    $asset = $event->getAsset();

    // Access updated asset properties
    // Perform custom actions based on the updated asset
});
```

### AssetDeleted

The `AssetDeleted` event object provides access to the deleted asset:

```php
Events::on('asset.deleted', function (AssetDeleted $event) {
    $asset = $event->getAsset();

    // Perform custom actions based on the deleted asset
    // For example, clean up related records, update cache, etc.
});
```

### VariantCreated

The `VariantCreated` event object provides access to the asset and the variant that was created:

```php
Events::on('variant.created', function (VariantCreated $event) {
    $asset = $event->getAsset();
    $variant = $event->getVariant();
    $variantName = $variant->name;

    // Access asset properties
    $assetId = $asset->id;

    // Perform custom actions based on the created variant
    // For example, update metadata, generate additional variants, etc.
});
```

## Use Cases

Here are some common use cases for using Asset Connect events:

### Logging Asset Activities

```php
// Log all asset activities
Events::on('asset.created', function (AssetCreated $event) {
    $asset = $event->getAsset();
    log_message('info', 'Asset created: ' . $asset->id . ' (' . $asset->file_name . ')');
});

Events::on('asset.updated', function (AssetUpdated $event) {
    $asset = $event->getAsset();
    log_message('info', 'Asset updated: ' . $asset->id . ' (' . $asset->file_name . ')');
});

Events::on('asset.deleted', function (AssetDeleted $event) {
    $asset = $event->getAsset();
    log_message('info', 'Asset deleted: ' . $asset->id);
});

Events::on('variant.created', function (VariantCreated $event) {
    $asset = $event->getAsset();
    $variant = $event->getVariant();
    $variantName = $variant->name;
    log_message('info', 'Variant created: ' . $variantName . ' for asset ' . $asset->id);
});
```

### Sending Notifications

```php
// Send a notification when an asset is created
Events::on('asset.created', function (AssetCreated $event) {
    $asset = $event->getAsset();

    // Get the entity that owns the asset
    $entity = $asset->getSubjectEntity();

    // Send a notification to the entity owner
    $emailService = service('email');
    $emailService->setTo($entity->email);
    $emailService->setSubject('New Asset Added');
    $emailService->setMessage('A new asset has been added to your account: ' . $asset->name);
    $emailService->send();
});
```

### Generating Additional Variants

```php
// Generate additional variants when a variant is created
Events::on('variant.created', function (VariantCreated $event) {
    $asset = $event->getAsset();
    $variant = $event->getVariant();
    $variantName = $variant->name;

    // Only process certain variants
    if ($variantName === 'thumbnail' && $asset->isImage()) {
        // Generate additional variants based on the thumbnail
        $imageService = service('image');
        $thumbnailPath = $asset->getVariantPath('thumbnail');

        // Generate a smaller version for mobile
        $imageService->withFile($thumbnailPath)
            ->resize(100, 100, true)
            ->save($asset->getPathDirname() . 'mobile_' . $asset->file_name);

        // Add the new variant to the asset
        $asset->addVariant('mobile', 'mobile_' . $asset->file_name);
        $asset->save();
    }
});
```

## Conclusion

Events provide a powerful way to extend the functionality of Asset Connect without modifying its core code. By listening for events, you can implement custom behaviors such as logging, notifications, additional processing, and integration with other systems.
