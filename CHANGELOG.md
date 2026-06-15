# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [v2.0.0](https://github.com/maniaba/asset-connect/tree/v2.0.0) - 2026-06-15

### Breaking Changes
- Reworked asset storage from filesystem-root paths to named storage disks.
- Asset rows now store `storage` plus a storage-relative `path`; physical roots and public URL prefixes live in `Config\Asset::$storages`.
- `local_path` can now be `null` for remote disks such as S3, FTP, SFTP, Google Cloud Storage, Azure Blob Storage, WebDAV, memory, or any custom Flysystem adapter.
- Public asset URLs now come from the configured storage disk `public_url` or Flysystem public URL support. Protected assets continue to go through AssetConnect routes and authorization.
- Default generated paths changed to the shorter `{date}/{random-id}/{file-name}` format.

### Added
- Added configurable storage disks with `defaultPublicStorage`, `defaultProtectedStorage`, and per-collection `setStorage()`.
- Added `StorageManager`, `FlysystemStorageDisk`, and storage-link support for exposing local public disks with `php spark asset-connect:storage-link`.
- Added support for official Flysystem adapters through driver-specific `setupStorage{Driver}()` methods, including S3-style and other remote storage backends.
- Added `asset-connect:migrate-paths` to normalize legacy rows into the new `storage` plus relative `path` model.
- Added `Asset::transferToStorage()` for moving or copying an existing asset between configured disks, including variants and database metadata updates.
- Added `copyToTemporaryFile()` and `withTemporaryFile()` on assets and variants for queue-safe processing of files stored on remote disks.
- Added internal asset metadata through `getInternalProperty()`, `setInternalProperty()`, and `getInternalProperties()` for backend-only state that should not be mixed with user custom properties.
- Added path helper methods `getDate()` and `getRandomId()` for readable collision-resistant custom path generators.

### Changed
- Asset persistence now writes through the configured storage disk instead of storing files directly against a physical base directory.
- Variants now retain their storage disk metadata, making transfers and remote storage workflows consistent for originals and generated files.
- The default path generator now produces shorter paths such as `2026-06-15/97847659691b3cae8857/photo.jpg`.
- Documentation was expanded for storage configuration, custom path generators, migration to 2.0.0, and remote-disk behavior.

### Benefits
- Storage is portable across environments because database rows no longer contain absolute filesystem roots.
- Remote storage is first-class: applications can use S3-compatible services, CDN-backed disks, or any Flysystem adapter without changing asset entities.
- Public and protected asset handling is clearer because visibility and URL generation belong to the storage disk configuration.
- Existing assets can be moved between disks without changing their relative path or manually rewriting database rows.
- Backend processing metadata can be stored separately from user-visible custom properties.
- Generated file paths are shorter, cleaner, and still collision-resistant.

### Upgrade Notes
- See the full upgrade guide: [Upgrade from 1.0.2 to 2.0.0](docs/upgrade-2.0.md).
- Run the package migrations so the `storage` column exists.
- Configure `Config\Asset::$storages` before using the new storage model.
- Run `php spark asset-connect:migrate-paths --storage public --dry-run` first, then rerun without `--dry-run` after reviewing the plan.
- For local public disks, run `php spark asset-connect:storage-link` or configure an equivalent web-server alias.
- Update any code that assumes `local_path` is always available; remote disks should be handled through storage streams or disk APIs.

## [v1.0.2](https://github.com/maniaba/asset-connect/tree/v1.0.2) - 2026-06-14

## What's Changed

* Enhance documentation workflows with versioning and theme support by @maniaba in https://github.com/maniaba/asset-connect/pull/48
* Reorder validation checks in AssetCollection to improve clarity and maintainability by @maniaba in https://github.com/maniaba/asset-connect/pull/50


## [v1.0.1](https://github.com/maniaba/asset-connect/tree/v1.0.1) - 2026-05-16

## What's Changed

* Add AI/agent orientation files for AssetConnect by @Copilot in https://github.com/maniaba/asset-connect/pull/38
* Add module-structure.md to mkdocs nav by @Copilot in https://github.com/maniaba/asset-connect/pull/39
* Implement AssetConnect serialization by @maniaba in https://github.com/maniaba/asset-connect/pull/41

## [v1.0.0](https://github.com/maniaba/asset-connect/tree/v1.0.0) - 2026-04-01

### Added
- Initial release of AssetConnect
- Support for associating files with any entity
- Asset collections for organizing files
- Custom properties for assets
- Secure asset storage with access control
- Type-safe API with full IDE support
- **Pending Assets system** for temporary file storage before final attachment

### Changed
- Refactor `singleFileCollection` to use custom validation rule by @maniaba in https://github.com/maniaba/asset-connect/pull/31

### Updated
- Update `codeigniter4/queue` to stable release `^v1.0.0` by @Copilot in https://github.com/maniaba/asset-connect/pull/33

## New Contributors
- @Copilot made their first contribution in https://github.com/maniaba/asset-connect/pull/33
