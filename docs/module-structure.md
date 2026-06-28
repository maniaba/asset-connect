# Module Structure

This document describes every module (namespace segment) under `src/` and its responsibility within the AssetConnect library.

---

## Top-Level Entry Point

### `AssetConnect.php`
`Maniaba\AssetConnect\AssetConnect`

The central orchestrator. Holds the `AssetModel`, manages cached assets per entity, and exposes the core add/get/delete operations that the entity trait delegates to.

---

## `Asset/`
`Maniaba\AssetConnect\Asset`

| File | Description |
|---|---|
| `Asset.php` | CI4 `Entity` subclass representing a stored file. Contains all properties (`id`, `entity_type`, `entity_id`, `collection`, `storage`, `name`, `file_name`, relative `path`, `mime_type`, `size`, `order`, `metadata`, timestamps). Provides `getUrl()`, `getTemporaryUrl()`, `download()`, `transferToStorage()`, `copyToTemporaryFile()`, `withTemporaryFile()`, `isImage()`, `isVideo()`, `isDocument()`, `getCustomProperty()`, `setCustomProperty()`, `getInternalProperty()`, `setInternalProperty()`, `save()`. |
| `AssetAdder.php` | Fluent builder returned by `entity->addAsset()`. Chains `withCustomProperties()`, `usingName()`, and terminates with `toAssetCollection()`. |
| `AssetAdderMultiple.php` | Variant of `AssetAdder` for adding multiple files at once. |
| `AssetMetadata.php` | Value object holding public custom properties, backend internal properties, and variant references. |
| `AssetPersistenceManager.php` | Persists uploaded files to the configured storage disk, writes database rows, queues variant processing, and cleans up failed writes. |
| `Interfaces/AssetAdderInterface.php` | Contract for fluent asset adders. Extends `AssetDefinitionInterface` and exposes `toAssetCollection()` and `metadata()`. |
| `Interfaces/AssetCollectionGetterInterface.php` | Read-only collection configuration contract passed to path generators. |
| `Interfaces/AssetCollectionDefinitionInterface.php` | Base interface every collection class must implement. Requires `definition(AssetCollectionSetterInterface)`. |
| `Interfaces/AssetCollectionSetterInterface.php` | Fluent setter interface for collection configuration: `allowedExtensions()`, `allowedMimeTypes()`, `setMaxFileSize()`, `singleFileCollection()`, `onlyKeepLatest()`, `setPathGenerator()`, `setStorage()`. |
| `Interfaces/AssetDefinitionInterface.php` | Shared fluent contract for asset name, file name, order, preserve-original flag, and custom properties. |
| `Interfaces/AuthorizableAssetCollectionDefinitionInterface.php` | Extends the base definition interface; adds `checkAuthorization(Asset): bool` for protected collections. |
| `Properties/BaseProperty.php` | Base wrapper for namespaced metadata properties. |
| `Properties/UserCustomProperty.php` | User-visible custom asset metadata stored under `user_custom`. |
| `Properties/InternalProperty.php` | Backend-only metadata stored under `internal`. |
| `Properties/AssetVariantProperty.php` | Metadata collection for registered asset variants. |
| `Traits/AssetFileInfoTrait.php` | File-name, MIME type, extension, and size helper methods. |
| `Traits/AssetMimeTypeTrait.php` | Type checks for images, videos, documents, audio, and related MIME groups. |

---

## `AssetCollection/`
`Maniaba\AssetConnect\AssetCollection`

| File | Description |
|---|---|
| `AssetCollection.php` | Represents a scoped view of assets for one entity + one collection. Provides `addAsset()`, `getAssets()`, `getFirstAsset()`, `getLastAsset()`, `deleteAssets()`. |
| `AssetCollectionDefinitionFactory.php` | Resolves a collection class name to an `AssetCollectionDefinitionInterface` instance. |
| `DefaultAssetCollection.php` | Built-in permissive collection (all types, no size limit). Used when no specific collection is given. |
| `SetupAssetCollection.php` | Mutable configuration object passed to `entity->setupAssetConnect()`. Holds default collection, path generator, file-name sanitiser, `preserveOriginal` flag, and primary-key attribute. |
| `Interfaces/SetupAssetCollectionInterface.php` | Interface for `SetupAssetCollection`. |

---

## `AssetVariants/`
`Maniaba\AssetConnect\AssetVariants`

| File | Description |
|---|---|
| `AssetVariant.php` | Represents a single generated variant (e.g. thumbnail). Holds `name`, `storage`, relative `path`, `file_name`, and helpers for `writeFile()`, `copyToTemporaryFile()`, and `withTemporaryFile()`. |
| `AssetVariants.php` | Implements `CreateAssetVariantsInterface`. Registers variant metadata, triggers `variant.created`, and stores the `onQueue` flag. |
| `AssetVariantsProcess.php` | Queues variant processing or runs it inline for a given asset and collection definition. |
| `AssetVariantsProcessor.php` | Executes existing variant closures and updates variant metadata after processing. |
| `Interfaces/AssetVariantsInterface.php` | Must be implemented by collections that define variants. Requires `variants(CreateAssetVariantsInterface, Asset)`. |
| `Interfaces/CreateAssetVariantsInterface.php` | Interface passed to `variants()`; exposes `assetVariant(name, closure, extension = null)` and `onQueue`. |

---

## `Commands/`
`Maniaba\AssetConnect\Commands`

| File | Command | Description |
|---|---|---|
| `MigrateLegacyAssetPaths.php` | `asset-connect:migrate-paths` | Assigns storage disk names to legacy rows and normalizes supported legacy filesystem paths. |
| `StorageLink.php` | `asset-connect:storage-link` | Creates public links for local storage disks that define `public_url`. |

---

## `Config/`
`Maniaba\AssetConnect\Config`

| File | Description |
|---|---|
| `Asset.php` | Library configuration class. Required arrays: `$entityKeyDefinitions`, `$collectionKeyDefinitions`. Optional: `$DBGroup`, `$defaultCollection`, `$defaultPathGenerator`, `$defaultUrlGenerator`, `$defaultPublicStorage`, `$defaultProtectedStorage`, `$storages`, `$tables`, `$queue`, `$pendingStorage`. |
| `Registrar.php` | CI4 auto-discovery registrar; registers routes and validation rules. |
| `Routes.php` | Registers AssetConnect routes through the configured URL generator. |
| `Services.php` | CI4 `Services` extension; exposes `assetAccessService()`. |

---

## `Contracts/`
`Maniaba\AssetConnect\Contracts`

| File | Description |
|---|---|
| `AssetConnectEntityInterface.php` | Contract that entities using `UseAssetConnectTrait` satisfy. |
| `AssetConnectModelInterface.php` | Contract that models using `UseAssetConnectModelTrait` satisfy. |

---

## `Controllers/`
`Maniaba\AssetConnect\Controllers`

| File | Description |
|---|---|
| `AssetConnectController.php` | Serves protected, temporary, and pending asset routes by delegating authorization and response creation to `AssetAccessService`. |

---

## `Database/`
`Maniaba\AssetConnect\Database`

| File | Description |
|---|---|
| `BaseMigration.php` | Abstract base for library migrations. |
| `Migrations/2025-06-18-180653_CreateAssetsTable.php` | Creates the `assets` table with all required columns and indexes. Run via `php spark migrate --namespace=Maniaba\\AssetConnect`. |
| `Migrations/2026-06-12-000000_AddStorageToAssetsTable.php` | Adds the `storage` disk column and indexes needed by the 2.0.0 storage model. |

---

## `Enums/`
`Maniaba\AssetConnect\Enums`

| File | Description |
|---|---|
| `AssetExtension.php` | Backed enum of file extensions. Helper methods: `images()`, `documents()`, `videos()`, `rasterGraphics()`, `vectorGraphics()`, `spreadsheets()`, `presentations()`. |
| `AssetMimeType.php` | Backed enum of MIME types. Static helpers: `isImage()`, `isVideo()`, `isDocument()`, `isAudio()`, `fromExtension()`, `fromAssetExtension()`, `getExtension()`. |
| `AssetVisibility.php` | Enum for `public` vs `protected` storage visibility. |

---

## `Events/`
`Maniaba\AssetConnect\Events`

| File | Event name | Description |
|---|---|---|
| `AssetCreated.php` | `asset.created` | Fired after a new asset is persisted. |
| `AssetUpdated.php` | `asset.updated` | Fired after an asset is updated. |
| `AssetDeleted.php` | `asset.deleted` | Fired after an asset is soft-deleted. |
| `VariantCreated.php` | `variant.created` | Fired after a variant file is generated. |
| `AssetEventInterface.php` | – | Common interface providing `getAsset()`. |

---

## `Exceptions/`
`Maniaba\AssetConnect\Exceptions`

Domain-specific exceptions: `AssetException`, `AuthorizationException`, `FileException`, `FileVariantException`, `InvalidArgumentException`, `PageException`, `PendingAssetException`.

---

## `Jobs/`
`Maniaba\AssetConnect\Jobs`

`AssetConnectJob` – CI4 Queue job handler. Processes queued asset work asynchronously.

---

## `Language/`
`Maniaba\AssetConnect\Language`

Validation and error message translations for 39 locales (Arabic, Bulgarian, Bengali, Bosnian, Chinese Simplified, Chinese Traditional, Czech, German, Greek, English, Spanish, Farsi, French, Gujarati, Hindi, Hungarian, Indonesian, Italian, Japanese, Korean, Lithuanian, Latvian, Malayalam, Dutch, Norwegian, Polish, Brazilian Portuguese, Portuguese, Romanian, Russian, Sinhala, Slovak, Serbian, Swedish, Tamil, Thai, Turkish, Ukrainian, Vietnamese).

---

## `Models/`
`Maniaba\AssetConnect\Models`

| File | Description |
|---|---|
| `BaseModel.php` | Base CI4 model that reads table names and `$DBGroup` from `Config\Asset`. |
| `AssetModel.php` | CI4 `Model` subclass with soft-delete enabled, scoped to the configured `$DBGroup` and table name. |

---

## `PathGenerator/`
`Maniaba\AssetConnect\PathGenerator`

| File | Description |
|---|---|
| `Interfaces/PathGeneratorInterface.php` | Contract for generating storage-relative directories for original assets and variants. |
| `DefaultPathGenerator.php` | Generates `{date}/{random-id}/` relative storage paths. |
| `PathGenerator.php` | Resolves and delegates to the correct generator, normalizing path separators for storage keys. |
| `PathGeneratorFactory.php` | Builds generator instances from config or collection definition. |
| `PathGeneratorHelper.php` | Shared path utilities. |

---

## `Pending/`
`Maniaba\AssetConnect\Pending`

Two-step upload system – upload first, attach to entity later.

| File | Description |
|---|---|
| `PendingAsset.php` | Represents a temporarily stored file. Factory methods: `createFromFile()`, `createFromRequest()`. Fluent builder: `usingName()`, `withCustomProperty()`. |
| `PendingAssetManager.php` | Facade over `PendingStorageInterface`. Methods: `make()`, `store()`, `fetchById()`, `consumeById()`, `deleteById()`. |
| `DefaultPendingStorage.php` | Protected Flysystem storage implementation; stores known pending object keys under the configured prefix. |
| `Interfaces/PendingOwnerResolverInterface.php` | Contract for resolving the current pending owner in API/JWT flows. |
| `Interfaces/PendingStorageInterface.php` | Contract for custom storage backends. |
| `Interfaces/PendingSecurityTokenInterface.php` | Contract for pending-asset token generation, retrieval, validation, and deletion. |
| `PendingSecurityToken/AbstractPendingSecurityToken.php` | Base implementation for token length, TTL validation, random token generation, and comparison. |
| `PendingSecurityToken/AbstractHmacPendingSecurityToken.php` | HMAC ownership validation base for session, cookie, and owner strategies. |
| `PendingSecurityToken/SessionPendingSecurityToken.php` | Default per-pending session-secret HMAC strategy. |
| `PendingSecurityToken/CookiePendingSecurityToken.php` | Per-pending HTTP-only cookie HMAC strategy. |
| `PendingSecurityToken/OwnerPendingSecurityToken.php` | API/JWT owner HMAC strategy using `PendingOwnerResolverInterface`. |

---

## `Repositories/`
`Maniaba\AssetConnect\Repositories`

`AssetRepository` / `Interfaces/AssetRepositoryInterface` – data-access abstraction over `AssetModel`; used internally by `AssetConnect`.

---

## `Storage/`
`Maniaba\AssetConnect\Storage`

| File | Description |
|---|---|
| `StorageManager.php` | Resolves configured storage disk names to disk instances and selects defaults by collection visibility. |
| `FlysystemStorageDisk.php` | Flysystem-backed disk implementation for read/write/delete/metadata/public URL/local path operations. |
| `StorageLinker.php` | Creates public filesystem links for local storage disks that define `public_url`. |
| `StorageLinkResult.php` | Value object returned by storage link operations. |
| `StorageLinkStatus.php` | Enum for storage link operation result statuses. |
| `TemporaryStorageFile.php` | Streams a stored asset or variant into a local temporary file for queue-safe remote processing. |
| `Interfaces/StorageDiskInterface.php` | Contract implemented by storage disks. |

---

## `Services/`
`Maniaba\AssetConnect\Services`

`AssetAccessService` / `Interfaces/AssetAccessServiceInterface` – optional service for applications that still serve files through a controller and need collection authorization checks.

---

## `Traits/`
`Maniaba\AssetConnect\Traits`

| File | Description |
|---|---|
| `UseAssetConnectTrait.php` | Add to any CI4 `Entity`. Provides `addAsset()`, `addAssetFromPending()`, `getAssets()`, `getFirstAsset()`, `getLastAsset()`, `deleteAssets()`, `collection()`. Requires `setupAssetConnect()` implementation. |
| `UseAssetConnectModelTrait.php` | Add to any CI4 `Model`. Hooks into `afterFind` to auto-inject `AssetConnect` into returned entities. |

---

## `UrlGenerator/`
`Maniaba\AssetConnect\UrlGenerator`

| File | Description |
|---|---|
| `Interfaces/PendingUrlGeneratorInterface.php` | Optional contract for URL generators that support pending asset preview/download routes. |
| `Interfaces/UrlGeneratorInterface.php` | Contract for registering route-backed asset URLs and returning route parameters. |
| `DefaultUrlGenerator.php` | Registers controller routes for protected, temporary, and pending URL generation. |
| `PendingUrlGenerator.php` | Generates pending asset preview/download URLs from the configured URL generator. |
| `TempUrlToken.php` | Creates and validates HMAC-signed temporary URL tokens. |
| `UrlGenerator.php` | Generates direct storage-disk URLs and temporary route URLs. |
| `Traits/UrlGeneratorTrait.php` | Shared URL-building helpers. |

---

## `Upgrade/`
`Maniaba\AssetConnect\Upgrade`

| File | Description |
|---|---|
| `LegacyAssetPathMigrationOptions.php` | Options value object for legacy path migration runs. |
| `LegacyAssetPathMigrationProgress.php` | Per-row progress value object emitted by the migrator. |
| `LegacyAssetPathMigrationSummary.php` | Summary counts for migrated, skipped, failed, and metadata-cleanup rows. |
| `LegacyAssetPathMigrator.php` | Converts supported 1.x asset path rows into the 2.0.0 storage disk + relative path model. |

---

## `Utils/`
`Maniaba\AssetConnect\Utils`

| File | Description |
|---|---|
| `Format.php` | `formatBytesHumanReadable(int $bytes, int $precision): string` |
| `PhpIni.php` | Reads `upload_max_filesize` / `post_max_size` from `php.ini`. |

---

## `Validation/`
`Maniaba\AssetConnect\Validation`

| File | Description |
|---|---|
| `AssetConnectValidator.php` | High-level validator; maps field names → collection definitions → CI4 validation rules. Methods: `setFieldCollectionDefinition()`, `validate()`, `validateFields()`, `validateDefinedFields()`, `validateFieldsFromRequest()`, `validateRequest()`, `getRules()`, `getErrors()`. |
| `ValidationRuleCollector.php` | Implements `AssetCollectionSetterInterface`; collects constraints and converts them to CI4 validation rule strings. |
