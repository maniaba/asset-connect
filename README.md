# AssetConnect for CodeIgniter 4

[![PHPUnit](https://github.com/maniaba/asset-connect/actions/workflows/phpunit.yml/badge.svg)](https://github.com/maniaba/asset-connect/actions/workflows/phpunit.yml)
[![PHPStan](https://github.com/maniaba/asset-connect/actions/workflows/phpstan.yml/badge.svg)](https://github.com/maniaba/asset-connect/actions/workflows/phpstan.yml)
[![Deptrac](https://github.com/maniaba/asset-connect/actions/workflows/deptrac.yml/badge.svg)](https://github.com/maniaba/asset-connect/actions/workflows/deptrac.yml)
[![Psalm](https://github.com/maniaba/asset-connect/actions/workflows/psalm.yml/badge.svg)](https://github.com/maniaba/asset-connect/actions/workflows/psalm.yml)
[![Docs](https://github.com/maniaba/asset-connect/actions/workflows/docs.yml/badge.svg)](https://github.com/maniaba/asset-connect/actions/workflows/docs.yml)

![PHP](https://img.shields.io/badge/PHP-%5E8.3-blue)
[![CodeIgniter](https://img.shields.io/badge/CodeIgniter-4.6+-blue.svg?style=flat-square)](http://codeigniter.com/)
![License](https://img.shields.io/badge/License-MIT-blue)

AssetConnect is a file management library for CodeIgniter 4 that allows you to associate files with any entity in your application.
It provides a robust, flexible solution for handling file uploads, storage, retrieval, variants, public custom metadata, backend-only internal metadata, and secure access control.

Storage is backed by named Flysystem disks. Asset records store the disk name and relative storage path, not absolute filesystem paths, so moving the application directory does not invalidate stored assets.

## Features

- Associate files with any CodeIgniter entity
- Organize files into typed asset collections
- Store files on named Flysystem disks, including local, S3-compatible, FTP, SFTP, Google Cloud Storage, Azure Blob Storage, WebDAV, memory, or custom adapters
- Generate public URLs from disk `public_url` configuration or serve protected assets through AssetConnect routes
- Generate variants inline or through CodeIgniter Queue
- Move existing assets between storage disks with `Asset::transferToStorage()`
- Process remote files through `copyToTemporaryFile()` and `withTemporaryFile()` when `local_path` is not available
- Keep public custom properties separate from backend-only internal properties
- Use pending assets for multi-step upload confirmation flows

## Requirements

- PHP 8.3 or higher
- CodeIgniter 4.6 or higher
- CodeIgniter Queue
- Flysystem 3

## Installation

Install the package:

```bash
composer require maniaba/asset-connect
```

Run the package migrations:

```bash
php spark migrate --namespace=Maniaba\\AssetConnect
```

If you use the default local public disk, expose it from `public/`:

```bash
php spark asset-connect:storage-link
```

## Example Usage

```php
// Add an asset to a user
$asset = $user->addAsset('/path/to/file.jpg')
    ->withCustomProperties([
        'title' => 'Profile Picture',
        'description' => 'User profile picture'
    ])
    ->toAssetCollection(ImagesCollection::class);

// Get all assets for a user
$assets = $user->getAssets();

// Get assets from a specific collection
$images = $user->getAssets(ImagesCollection::class);

// Get the URL to an asset
$url = $user->getFirstAsset(ImagesCollection::class)->getUrl();

// Delete assets from a specific collection
$user->deleteAssets(ImagesCollection::class);
```

## Storage Quick Start

AssetConnect stores only `storage` and a storage-relative `path` in the database. Configure physical roots, visibility, and public URL prefixes in `Config\Asset`:

```php
public string $defaultPublicStorage = 'public';
public string $defaultProtectedStorage = 'protected';

public array $storages = [
    'public' => [
        'driver'     => 'local',
        'root'       => WRITEPATH . 'asset-connect/public',
        'public_url' => 'assets/storage',
        'visibility' => 'public',
    ],
    'protected' => [
        'driver'     => 'local',
        'root'       => WRITEPATH . 'asset-connect/protected',
        'visibility' => 'protected',
    ],
];
```

Remote disks can be added through Flysystem adapters and driver-specific setup methods such as `setupStorageAwsS3()`. Public remote disks should define an HTTP `public_url`; protected disks are served through AssetConnect routes and authorization.

For remote disks, `local_path` can be `null`. Use temporary-file helpers for processors that require a local filesystem path:

```php
$asset->withTemporaryFile(static function (string $source): void {
    service('image')
        ->withFile($source)
        ->resize(1200, 900, true)
        ->save(WRITEPATH . 'cache/processed.jpg');
});
```

Move an existing asset and its variants to another configured disk:

```php
$asset->transferToStorage('protected');
$asset->transferToStorage('s3_public', deleteSource: false);
```

## Upgrade From 1.0.2 To 2.0.0

Version 2.0.0 changes storage from filesystem-root paths to named storage disks.

Read the full guide before migrating production data: [Upgrade from 1.0.2 to 2.0.0](docs/upgrade-2.0.md).

## Documentation

Comprehensive documentation is available at [https://maniaba.github.io/asset-connect/](https://maniaba.github.io/asset-connect/).

Versioned documentation is published with `mike`. The Docs workflow validates docs on pull requests, publishes every push to `develop` as the `develop` docs version, and publishes release documentation from release tags such as `v2.0.0`. Stable releases move the `latest` alias and default redirect to the released docs version.

For local checks and manual publishing:

```bash
pip install -r docs/requirements.txt
mkdocs build --strict
mike deploy --push develop
mike deploy --push --update-aliases 2.0.0 latest
mike set-default --push latest
```

Find yourself stuck using the package? Found a bug? Do you have general questions or suggestions for improving the media library? Feel free to create an issue on GitHub, we'll try to address it as soon as possible.

## Testing

Run the test suite with:

```bash
composer test
```

For more detailed testing options:

```bash
# Run with code coverage
composer test -- --coverage-html=build/coverage

# Run static analysis
composer analyze
```

## Changelog

All notable changes to this project are documented in the [CHANGELOG.md](CHANGELOG.md) file.

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details on how to contribute to this project.

## Security

If you discover a security vulnerability, please send an email to [maniaba@outlook.com](mailto:maniaba@outlook.com) instead of using the issue tracker. All security vulnerabilities will be promptly addressed.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
