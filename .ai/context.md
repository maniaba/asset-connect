# AssetConnect – AI Context

## Project Overview

**AssetConnect** (`maniaba/asset-connect`) is a file-management library for **CodeIgniter 4**.
It lets you associate any file with any CI4 entity through a trait-based API, organise those files into typed collections, generate signed/temporary URLs, validate uploads, and process file variants asynchronously via a queue.

- **Version:** 2.0.0
- **License:** MIT
- **Namespace root:** `Maniaba\AssetConnect`
- **Docs:** <https://maniaba.github.io/asset-connect/>
- **Repository:** <https://github.com/maniaba/asset-connect>

---

## Requirements

| Dependency | Version |
|---|---|
| PHP | ^8.3 |
| CodeIgniter 4 | ^4.6 |
| CodeIgniter Queue | ^1.0 |

---

## Architecture at a Glance

```
src/
├── Asset/                  Core Asset entity + AssetAdder fluent builder
├── AssetCollection/        Collection configuration, factory, default collection
├── AssetVariants/          Variant model, processing pipeline, queue job
├── Config/                 Asset config, Routes, Services, Registrar
├── Contracts/              Entity & Model interface contracts
├── Controllers/            Secure asset access controller
├── Database/               BaseMigration + CreateAssetsTable migration
├── Enums/                  AssetExtension, AssetMimeType, AssetVisibility
├── Events/                 AssetCreated, AssetUpdated, AssetDeleted, VariantCreated
├── Exceptions/             Domain exceptions
├── Jobs/                   AssetConnectJob (queue handler)
├── Language/               i18n strings for 30+ locales
├── Models/                 AssetModel (soft-delete, CI4 Model)
├── PathGenerator/          DefaultPathGenerator + factory + interface
├── Pending/                PendingAsset, PendingAssetManager, DefaultPendingStorage
├── Repositories/           AssetRepository + interface
├── Services/               AssetAccessService (authorisation)
├── Storage/                Flysystem-backed named storage disks
├── Traits/                 UseAssetConnectTrait, UseAssetConnectModelTrait
├── UrlGenerator/           DefaultUrlGenerator, TempUrlToken, trait, interface
├── Utils/                  Format (human-readable size), PhpIni helpers
└── Validation/             AssetConnectValidator, ValidationRuleCollector
```

---

## Key Concepts

### Asset entity (`src/Asset/Asset.php`)
Represents a stored file. Contains `id`, `entity_type`, `entity_id`, `collection`, `storage`, `name`, `file_name`, relative `path`, `mime_type`, `size`, `order`, `metadata`, timestamps, and soft-delete.
Key methods: `getUrl()`, `getTemporaryUrl()`, `download()`, `isImage()`, `isVideo()`, `isDocument()`, `getCustomProperty()`, `setCustomProperty()`, `save()`.

### Traits
- **`UseAssetConnectTrait`** – add to any CI4 `Entity`; provides `addAsset()`, `addAssetFromPending()`, `getAssets()`, `getFirstAsset()`, `getLastAsset()`, `deleteAssets()`, `collection()`.
- **`UseAssetConnectModelTrait`** – add to any CI4 `Model`; auto-loads AssetConnect after `find*` calls.

### Asset Collections
Implement `AssetCollectionDefinitionInterface` to define allowed extensions, MIME types, max file size, single-file mode, storage disk, path generator, etc. via a fluent `AssetCollectionSetterInterface`.
Optionally implement `AssetVariantsInterface` for thumbnail/resize variants and `AuthorizableAssetCollectionDefinitionInterface` for access-controlled (private) storage.

### Pending Assets
Two-step upload: upload → `PendingAssetManager::store()` → get ID → later `entity->addAssetFromPending($pending)`.
Pending files expire (configurable TTL) and are auto-cleaned by the queue job.

### Configuration (`Config\Asset`)
Two **required** arrays:
```php
$entityKeyDefinitions    = [MyEntity::class => 'my_entity'];
$collectionKeyDefinitions = [MyCollection::class => 'my_collection'];
```
Optional: `$DBGroup`, `$defaultCollection`, `$defaultPathGenerator`, `$defaultUrlGenerator`, `$defaultPublicStorage`, `$defaultProtectedStorage`, `$storages`, `$tables`, `$queue`, `$pendingStorage`.

### Events
`asset.created`, `asset.updated`, `asset.deleted`, `variant.created` – listen via CI4 `Events::on()`.

### Validation
`AssetConnectValidator` generates CI4-compatible rules from collection definitions; integrates seamlessly with CI4's native validator.

---

## Development Commands

```bash
composer test            # PHPUnit tests
composer analyze         # PHPStan + Psalm + Rector (dry-run)
composer cs              # PHP CS Fixer (dry-run)
composer cs-fix          # PHP CS Fixer (apply)
composer inspect         # Deptrac layer check
composer ci              # Full CI pipeline
```

---

## Database

Single migration creates the `assets` table:

```bash
php spark migrate --namespace=Maniaba\\AssetConnect
```

The `assets` table stores `storage` plus a storage-relative `path`; it does not store absolute filesystem paths.

---

## Testing

- Framework: PHPUnit 10/11/12
- Static analysis: PHPStan (level max) + Psalm
- Mutation testing: Infection
- VFS: mikey179/vfsstream (virtual filesystem)
- Factories/fakes: FakerPHP

Tests live in `tests/` with support files in `tests/_support/`.
