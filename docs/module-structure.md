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
| `Asset.php` | CI4 `Entity` subclass representing a stored file. Contains all properties (`id`, `entity_type`, `entity_id`, `collection`, `storage`, `name`, `file_name`, relative `path`, `mime_type`, `size`, `order`, `metadata`, timestamps). Provides `getUrl()`, `getTemporaryUrl()`, `download()`, `isImage()`, `isVideo()`, `isDocument()`, `getCustomProperty()`, `setCustomProperty()`, `save()`. |
| `AssetAdder.php` | Fluent builder returned by `entity->addAsset()`. Chains `withCustomProperties()`, `usingName()`, and terminates with `toAssetCollection()`. |
| `AssetAdderMultiple.php` | Variant of `AssetAdder` for adding multiple files at once. |
| `AssetMetadata.php` | Value object holding custom properties and variant references. |
| `Interfaces/AssetCollectionDefinitionInterface.php` | Base interface every collection class must implement. Requires `definition(AssetCollectionSetterInterface)`. |
| `Interfaces/AssetCollectionSetterInterface.php` | Fluent setter interface for collection configuration: `allowedExtensions()`, `allowedMimeTypes()`, `setMaxFileSize()`, `singleFileCollection()`, `onlyKeepLatest()`, `setPathGenerator()`, `setStorage()`. |
| `Interfaces/AuthorizableAssetCollectionDefinitionInterface.php` | Extends the base definition interface; adds `checkAuthorization(Entity, Asset): bool` for private collections. |

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
| `AssetVariant.php` | Represents a single generated variant (e.g. thumbnail). Holds `name`, `storage`, relative `path`, `file_name`, and `writeFile()` helper. |
| `AssetVariants.php` | Implements `CreateAssetVariantsInterface`. Collects variant closures and the `onQueue` flag. |
| `AssetVariantsProcess.php` | Executes the variant closures for a given asset. |
| `AssetVariantsProcessor.php` | Orchestrates variant processing; dispatches to queue or runs inline. |
| `Interfaces/AssetVariantsInterface.php` | Must be implemented by collections that define variants. Requires `variants(CreateAssetVariantsInterface, Asset)`. |
| `Interfaces/CreateAssetVariantsInterface.php` | Interface passed to `variants()`; exposes `assetVariant(name, closure)` and `onQueue`. |

---

## `Config/`
`Maniaba\AssetConnect\Config`

| File | Description |
|---|---|
| `Asset.php` | Library configuration class. Required arrays: `$entityKeyDefinitions`, `$collectionKeyDefinitions`. Optional: `$DBGroup`, `$defaultCollection`, `$defaultPathGenerator`, `$defaultUrlGenerator`, `$defaultPublicStorage`, `$defaultProtectedStorage`, `$storages`, `$tables`, `$queue`, `$pendingStorage`. |
| `Registrar.php` | CI4 auto-discovery registrar; registers routes and validation rules. |
| `Routes.php` | Defines the secure asset-access route. |
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

Contains the secure-download controller that gates access to non-public assets through `AuthorizableAssetCollectionDefinitionInterface::checkAuthorization()`.

---

## `Database/`
`Maniaba\AssetConnect\Database`

| File | Description |
|---|---|
| `BaseMigration.php` | Abstract base for library migrations. |
| `Migrations/2025-06-18-180653_CreateAssetsTable.php` | Creates the `assets` table with all required columns and indexes. Run via `php spark migrate --namespace=Maniaba\\AssetConnect`. |

---

## `Enums/`
`Maniaba\AssetConnect\Enums`

| File | Description |
|---|---|
| `AssetExtension.php` | Backed enum of file extensions. Helper methods: `images()`, `documents()`, `videos()`, `rasterGraphics()`, `vectorGraphics()`, `spreadsheets()`, `presentations()`. |
| `AssetMimeType.php` | Backed enum of MIME types. Static helpers: `isImage()`, `isVideo()`, `isDocument()`, `isAudio()`, `fromExtension()`, `fromAssetExtension()`, `getExtension()`. |
| `AssetVisibility.php` | Enum for `public` vs `private` visibility. |

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

Domain-specific exceptions: `AssetException`, `FileException`, `InvalidArgumentException`, `PendingAssetException`.

---

## `Jobs/`
`Maniaba\AssetConnect\Jobs`

`AssetConnectJob` – CI4 Queue job handler. Processes asset variants and cleans up expired pending assets asynchronously.

---

## `Language/`
`Maniaba\AssetConnect\Language`

Validation and error message translations for 30+ locales (Arabic, Bulgarian, Bengali, Bosnian, Chinese (TW), Czech, German, Greek, English, Farsi, French, Gujarati, Indonesian, Italian, Japanese, Korean, Latvian, Malayalam, Dutch, Norwegian, Polish, Brazilian Portuguese, Portuguese, Romanian, Russian, Sinhala, Serbian, Swedish, Thai, Turkish, Ukrainian, Vietnamese).

---

## `Models/`
`Maniaba\AssetConnect\Models`

`AssetModel` – CI4 `Model` subclass with soft-delete enabled, scoped to the configured `$DBGroup` and table name.

---

## `PathGenerator/`
`Maniaba\AssetConnect\PathGenerator`

| File | Description |
|---|---|
| `PathGeneratorInterface.php` | Contract for generating storage-relative directories for original assets and variants. |
| `DefaultPathGenerator.php` | Generates `assets/{date}/{time}/` relative storage paths. |
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
| `PendingAssetManager.php` | Facade over `PendingStorageInterface`. Methods: `make()`, `store()`, `fetchById()`, `deleteById()`, `cleanExpiredPendingAssets()`. |
| `DefaultPendingStorage.php` | Filesystem implementation; stores file + `metadata.json` per asset. |
| `Interfaces/PendingStorageInterface.php` | Contract for custom storage backends. |

---

## `Repositories/`
`Maniaba\AssetConnect\Repositories`

`AssetRepository` / `AssetRepositoryInterface` – data-access abstraction over `AssetModel`; used internally by `AssetConnect`.

---

## `Storage/`
`Maniaba\AssetConnect\Storage`

| File | Description |
|---|---|
| `StorageManager.php` | Resolves configured storage disk names to disk instances and selects defaults by collection visibility. |
| `FlysystemStorageDisk.php` | Flysystem-backed disk implementation for read/write/delete/metadata/public URL/local path operations. |
| `StorageLinker.php` | Creates public filesystem links for local storage disks that define `public_url`. |
| `StorageLinkResult.php` | Value object returned by storage link operations. |
| `Interfaces/StorageDiskInterface.php` | Contract implemented by storage disks. |

---

## `Services/`
`Maniaba\AssetConnect\Services`

`AssetAccessService` / `AssetAccessServiceInterface` – optional service for applications that still serve files through a controller and need collection authorization checks.

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
| `UrlGeneratorInterface.php` | Contract: `getUrl()`, `getUrlRelative()`, `getTemporaryUrl()`, `getTemporaryUrlRelative()`. |
| `DefaultUrlGenerator.php` | Registers legacy/controller routes for temporary or custom URL generation. |
| `TempUrlToken.php` | Creates and validates HMAC-signed temporary URL tokens. |
| `UrlGenerator.php` | Generates direct storage-disk URLs and temporary route URLs. |
| `Traits/UrlGeneratorTrait.php` | Shared URL-building helpers. |

---

## `Utils/`
`Maniaba\AssetConnect\Utils`

| File | Description |
|---|---|
| `Format.php` | `humanReadableSize(int $bytes, int $precision): string` |
| `PhpIni.php` | Reads `upload_max_filesize` / `post_max_size` from `php.ini`. |

---

## `Validation/`
`Maniaba\AssetConnect\Validation`

| File | Description |
|---|---|
| `AssetConnectValidator.php` | High-level validator; maps field names → collection definitions → CI4 rule strings. Methods: `setFieldCollectionDefinition()`, `validate()`, `validateFields()`, `validateDefinedFields()`, `validateFieldsFromRequest()`, `validateDefinedFieldsFromRequest()`, `getRules()`, `getErrors()`. |
| `ValidationRuleCollector.php` | Implements `AssetCollectionSetterInterface`; collects constraints and converts them to CI4 validation rule strings. |
