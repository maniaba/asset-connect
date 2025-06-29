# CodeIgniter Asset Connect Documentation

CodeIgniter Asset Connect is a file management library for CodeIgniter 4 that allows you to associate files with any entity in your application. It's inspired by [spatie/laravel-medialibrary](https://github.com/spatie/laravel-medialibrary) but built specifically for CodeIgniter 4.

## Features

- Associate files with any entity in your application
- Organize files into collections
- Store custom properties with your files
- Easily retrieve and manipulate files
- Secure asset storage with access control
- Type-safe API with full IDE support

## Requirements

- PHP 8.1 or higher
- CodeIgniter 4.3 or higher

## Quick Example

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
```

## License

This library is licensed under the MIT License - see the [LICENSE](https://github.com/maniaba/file-connect/blob/main/LICENSE.md) file for details.
