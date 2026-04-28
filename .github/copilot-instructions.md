# GitHub Copilot Instructions – AssetConnect

## Project Identity

This is **AssetConnect** (`maniaba/asset-connect`), a CodeIgniter 4 file-management library.  
Namespace root: `Maniaba\AssetConnect` | PHP ^8.3 | CI4 ^4.6 | MIT

---

## Code Style & Standards

- **Strict types** – every PHP file must start with `declare(strict_types=1);`
- **PSR-12** + CodeIgniter coding standard (`codeigniter/coding-standard`)
- **Final classes** preferred for concrete implementations; use interfaces and traits for extension points
- **No `mixed` returns** unless unavoidable – prefer typed returns (union types, nullable)
- All new methods need a docblock only when the signature alone is insufficient
- Run `composer cs-fix` before committing

---

## Architecture Rules (Deptrac)

Respect the layer boundaries enforced by `deptrac.yaml`.  
Do **not** add cross-layer dependencies. Typical layers:

| Layer | Contents |
|---|---|
| Config | `src/Config/` |
| Contracts | `src/Contracts/` |
| Enums | `src/Enums/` |
| Domain | `src/Asset/`, `src/AssetCollection/`, `src/AssetVariants/`, `src/Pending/` |
| Infrastructure | `src/Models/`, `src/Repositories/`, `src/PathGenerator/`, `src/UrlGenerator/` |
| Application | `src/Services/`, `src/Jobs/`, `src/Validation/`, `src/Events/` |
| Presentation | `src/Controllers/`, `src/Traits/` |

---

## Key Patterns

### Adding Asset Support to an Entity

```php
use CodeIgniter\Entity\Entity;
use Maniaba\AssetConnect\Traits\UseAssetConnectTrait;
use Maniaba\AssetConnect\AssetCollection\Interfaces\SetupAssetCollectionInterface;

class Product extends Entity
{
    use UseAssetConnectTrait;

    public function setupAssetConnect(SetupAssetCollectionInterface $setup): void
    {
        $setup->setDefaultCollectionDefinition(ProductImagesCollection::class);
    }
}
```

### Defining a Collection

```php
use Maniaba\AssetConnect\Asset\Interfaces\AssetCollectionDefinitionInterface;
use Maniaba\AssetConnect\Asset\Interfaces\AssetCollectionSetterInterface;
use Maniaba\AssetConnect\AssetVariants\Interfaces\AssetVariantsInterface;
use Maniaba\AssetConnect\AssetVariants\Interfaces\CreateAssetVariantsInterface;
use Maniaba\AssetConnect\Asset\Asset;
use Maniaba\AssetConnect\Enums\AssetExtension;
use Maniaba\AssetConnect\Enums\AssetMimeType;

class ProductImagesCollection implements AssetCollectionDefinitionInterface, AssetVariantsInterface
{
    public function definition(AssetCollectionSetterInterface $definition): void
    {
        $definition
            ->allowedExtensions(...AssetExtension::images())
            ->allowedMimeTypes(AssetMimeType::IMAGE_JPEG, AssetMimeType::IMAGE_PNG)
            ->setMaxFileSize(5 * 1024 * 1024);
    }

    public function variants(CreateAssetVariantsInterface $variants, Asset $asset): void
    {
        // define variants here
    }
}
```

### Required Config Registration

Every entity and collection **must** be registered in `Config\Asset`:

```php
public array $entityKeyDefinitions    = [Product::class => 'product'];
public array $collectionKeyDefinitions = [ProductImagesCollection::class => 'product_images'];
```

---

## What Copilot Should (and Should Not) Do

### DO
- Suggest `declare(strict_types=1)` in new PHP files
- Use existing enums (`AssetExtension`, `AssetMimeType`, `AssetVisibility`) instead of raw strings
- Suggest implementing `AuthorizableAssetCollectionDefinitionInterface` when collections hold private files
- Use `PendingAssetManager` for two-step upload flows
- Use `AssetConnectValidator` (or compose with CI4 `validation` service) for file validation
- Fire or handle the library events (`asset.created`, `asset.updated`, etc.) for side-effects
- Prefer queue processing (`$variants->onQueue = true`) for expensive variant generation

### DON'T
- Bypass the `collectionKeyDefinitions` / `entityKeyDefinitions` registration
- Store raw file paths in business logic – always go through `Asset::getUrl()` / `Asset::getAbsolutePath()`
- Add new Composer dependencies without checking `composer-unused.php` and security advisories
- Remove or weaken soft-delete logic in `AssetModel`
- Mix presentation and domain concerns

---

## Testing Guidelines

- New features need PHPUnit tests in `tests/`
- Use `mikey179/vfsstream` for filesystem tests
- Use `FakerPHP` factories for test data
- Run `composer test` to verify all tests pass
- Run `composer analyze` for static analysis before opening a PR
