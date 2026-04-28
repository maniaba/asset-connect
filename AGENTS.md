# AGENTS

Guidelines for AI coding agents working on the **AssetConnect** (`maniaba/asset-connect`) repository.

---

## Repository at a Glance

| Item | Value |
|---|---|
| Type | PHP library (CodeIgniter 4) |
| Namespace | `Maniaba\AssetConnect` |
| PHP version | ^8.3 |
| Framework | CodeIgniter ^4.6 + CodeIgniter Queue ^1.0 |
| Tests | PHPUnit 10/11/12 |
| Static analysis | PHPStan (strict) + Psalm |
| Style | PSR-12 + CodeIgniter coding standard |
| Migration | `php spark migrate --namespace=Maniaba\\AssetConnect` |

---

## Project Layout

```
src/                   Library source code (see docs/module-structure.md)
tests/                 PHPUnit test suite
tests/_support/        Factories, fixtures, mock implementations
docs/                  MkDocs documentation
.github/workflows/     CI workflows (phpunit, phpstan, psalm, deptrac, docs)
```

For a detailed breakdown of every module see [`docs/module-structure.md`](docs/module-structure.md).  
For AI-context summary see [`.ai/context.md`](.ai/context.md).

---

## Environment Setup

```bash
composer install
```

No database server is required for the test suite (uses vfsstream + SQLite in-memory where needed).

---

## Commands

| Purpose | Command |
|---|---|
| Run tests | `composer test` |
| Run with coverage | `composer test -- --coverage-html=build/coverage` |
| Static analysis | `composer analyze` |
| Fix code style | `composer cs-fix` |
| Check code style (dry-run) | `composer cs` |
| Deptrac layer check | `composer inspect` |
| Full CI pipeline | `composer ci` |
| Mutation testing | `composer mutate` |

---

## Coding Conventions

1. **`declare(strict_types=1)`** at the top of every PHP file.
2. Use **`final`** for all concrete classes that are not intended for extension.
3. Implement extension points through **interfaces and traits**, not inheritance.
4. Never use `mixed` when a specific type can be expressed.
5. Docblocks are only required when the signature alone is ambiguous.
6. All public API changes must be accompanied by tests.

---

## Before Submitting a Change

1. `composer cs-fix` – fix code style.
2. `composer test` – all tests must pass.
3. `composer analyze` – no new PHPStan or Psalm errors.
4. `composer inspect` – Deptrac must not report new violations.
5. If a new collection, entity, or configuration option is added, update `docs/` accordingly.

---

## Required Registration

Any new **entity** or **collection** added to the library examples or tests must be listed in `Config\Asset`:

```php
public array $entityKeyDefinitions    = [MyEntity::class    => 'my_entity'];
public array $collectionKeyDefinitions = [MyCollection::class => 'my_collection'];
```

Failure to register will cause a runtime error.

---

## Adding New Features

### New Asset Collection
1. Create a class in `src/AssetCollection/` (or app-space for consumer examples).
2. Implement `AssetCollectionDefinitionInterface` (+ `AssetVariantsInterface` and/or `AuthorizableAssetCollectionDefinitionInterface` as needed).
3. Register in config.
4. Add tests under `tests/`.

### New Path Generator
1. Implement `PathGeneratorInterface` (`getPath(Asset): string`).
2. Set via `$setup->setPathGenerator()` or `$config->defaultPathGenerator`.

### New URL Generator
1. Implement `UrlGeneratorInterface`.
2. Set via `$config->defaultUrlGenerator`.

### New Pending Storage Backend
1. Implement `PendingStorageInterface`.
2. Set via `$config->pendingStorage`.

---

## Testing Guidelines

- Mirror the `src/` directory structure in `tests/`.
- Use `mikey179/vfsstream` for filesystem interactions.
- Use `FakerPHP` for generating test data.
- Mock only at architectural boundaries (interfaces), not internal classes.
- Do **not** remove or weaken existing tests.

---

## Security Notes

- Never expose the raw filesystem path in responses; always use `Asset::getUrl()`.
- Private assets must go through the authorisation controller; implement `AuthorizableAssetCollectionDefinitionInterface`.
- Temporary URL tokens are HMAC-signed; do not bypass `TempUrlToken`.
- Do not commit secrets or credentials.

---

## Out of Scope

- Changes to CI4 core or CodeIgniter Queue internals.
- Adding non-PHP runtimes or build tools.
- Breaking changes to the public API without a corresponding CHANGELOG entry and version bump.
