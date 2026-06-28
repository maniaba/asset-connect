# Upgrade from 3.0.0 to 3.1.0

AssetConnect 3.1.0 is a backward-compatible minor release. It adds route-backed pending asset preview/download URLs and tightens file-name sanitization for newly stored assets.

No database migration is required for this upgrade.

## Update the Package

Update the Composer constraint and install the new release:

```bash
composer require maniaba/asset-connect:^3.1
```

Do not add a `version` field to `composer.json`; AssetConnect is versioned from Git tags.

## Pending Preview and Download URLs

Stored pending assets can now expose route-backed URLs before they are attached to an entity:

```php
$pending->getPreviewUrl();
$pending->getDownloadUrl();
$pending->getPreviewUrlRelative();
$pending->getDownloadUrlRelative();
```

Serialized pending assets also include:

```php
[
    'preview_url' => $pending->getPreviewUrl(),
    'download_url' => $pending->getDownloadUrl(),
]
```

The default route shape is:

```text
/assets/pending/{pendingId}/{filename}
```

The route serves the file inline by default. Add `?download=force` to force a browser download.

## Custom URL Generators

If your application uses the default URL generator, no route changes are required.

If you use a custom URL generator and want pending preview/download URLs, add `PendingUrlGeneratorInterface` to your existing generator:

```php
use CodeIgniter\Router\RouteCollection;
use Maniaba\AssetConnect\Controllers\AssetConnectController;
use Maniaba\AssetConnect\Pending\PendingAsset;
use Maniaba\AssetConnect\UrlGenerator\Interfaces\PendingUrlGeneratorInterface;
use Maniaba\AssetConnect\UrlGenerator\Interfaces\UrlGeneratorInterface;

final class CustomUrlGenerator implements UrlGeneratorInterface, PendingUrlGeneratorInterface
{
    // Existing UrlGeneratorInterface methods stay in place.

    public static function routes(RouteCollection &$routes): void
    {
        $routes->group('assets', static function (RouteCollection $routes): void {
            // Existing protected and temporary routes stay in place.
            $routes->get('pending/(:segment)/(:segment)', [AssetConnectController::class, 'pending/$1'], [
                'priority' => 100,
                'as'       => 'asset-connect.pending',
            ]);
        });
    }

    public static function pendingParams(PendingAsset $pendingAsset): array
    {
        return [
            'asset-connect.pending' => [$pendingAsset->id, $pendingAsset->file_name],
        ];
    }
}
```

Existing protected asset and temporary URL methods do not need to change.

## Custom Pending Storage

`DefaultPendingStorage` supports remote/protected disks by streaming the pending file to a local temporary source before the pending response is built.

If you use a custom `PendingStorageInterface`, make sure `fetchById()` returns a `PendingAsset` backed by a readable local temporary file. This is required for pending preview/download responses.

## File Name Sanitization

Newly stored asset file names are sanitized more strictly. AssetConnect now also applies CodeIgniter's `sanitize_filename()` helper and filters the result through `Config\App::$permittedURIChars`.

Existing stored assets are not rewritten. The change only affects new uploads or files stored after the upgrade.


If your application uses a custom URL generator or custom pending storage, also verify one pending upload flow end to end: upload, inspect the returned pending object, open `preview_url`, and open `download_url`.
