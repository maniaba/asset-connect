# AssetConnect for CodeIgniter 4

[![PHPUnit](https://github.com/maniaba/asset-connect/actions/workflows/phpunit.yml/badge.svg)](https://github.com/maniaba/asset-connect/actions/workflows/phpunit.yml)
[![PHPStan](https://github.com/maniaba/asset-connect/actions/workflows/phpstan.yml/badge.svg)](https://github.com/maniaba/asset-connect/actions/workflows/phpstan.yml)
[![Deptrac](https://github.com/maniaba/asset-connect/actions/workflows/deptrac.yml/badge.svg)](https://github.com/maniaba/asset-connect/actions/workflows/deptrac.yml)
[![Psalm](https://github.com/maniaba/asset-connect/actions/workflows/psalm.yml/badge.svg)](https://github.com/maniaba/asset-connect/actions/workflows/psalm.yml)


![PHP](https://img.shields.io/badge/PHP-%5E8.3-blue)
[![CodeIgniter](https://img.shields.io/badge/CodeIgniter-4.6+-blue.svg?style=flat-square)](http://codeigniter.com/)
![License](https://img.shields.io/badge/License-MIT-blue)

AssetConnect is a file management library for CodeIgniter 4 that allows you to associate files with any entity in your application.
It provides a robust, flexible solution for handling file uploads, storage, and retrieval with powerful features like collections, custom properties, and secure access control.

## Requirements

- PHP 8.3 or higher
- CodeIgniter 4.6 or higher
- CodeIgniter Queue

## Example Usage

```php
// Add an asset to a user
$asset = $user->addAsset('/path/to/file.jpg')
    ->withCustomProperties([
        'title' => 'Profile Picture',
        'description' => 'User profile picture'
    ])
    ->toAssetCollection();

// Get all assets for a user
$assets = $user->getAssets();

// Get the URL to an asset
$url = $user->getFirstAsset()->getUrl();

// Delete assets from a specific collection
$user->deleteAssets(ImagesCollection::class);
```

## Documentation

Comprehensive documentation is available at [https://maniaba.github.io/asset-connect/](https://maniaba.github.io/asset-connect/).

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
