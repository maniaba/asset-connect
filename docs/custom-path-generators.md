# Custom Path Generators

Path generators determine the relative storage path for stored assets. They do not choose the physical filesystem root and they should not return absolute paths. Storage roots and public URL prefixes are configured through named storage disks.

## Creating Custom Path Generators

Create a custom path generator by implementing `PathGeneratorInterface`:

```php
use Maniaba\AssetConnect\Asset\Interfaces\AssetCollectionGetterInterface;
use Maniaba\AssetConnect\PathGenerator\Interfaces\PathGeneratorInterface;
use Maniaba\AssetConnect\PathGenerator\PathGeneratorHelper;

final class CustomPathGenerator implements PathGeneratorInterface
{
    public function getFileRelativePath(
        PathGeneratorHelper $generatorHelper,
        AssetCollectionGetterInterface $collection,
    ): string {
        return $generatorHelper->getPathString(
            $generatorHelper->getDate(),
            $generatorHelper->getRandomId(),
        ) . '/';
    }

    public function getPath(
        PathGeneratorHelper $generatorHelper,
        AssetCollectionGetterInterface $collection,
    ): string {
        return $this->getFileRelativePath($generatorHelper, $collection);
    }

    public function getFileRelativePathForVariants(
        PathGeneratorHelper $generatorHelper,
        AssetCollectionGetterInterface $collection,
    ): string {
        return $this->getFileRelativePath($generatorHelper, $collection) . 'variants/';
    }

    public function getPathForVariants(
        PathGeneratorHelper $generatorHelper,
        AssetCollectionGetterInterface $collection,
    ): string {
        return $this->getFileRelativePathForVariants($generatorHelper, $collection);
    }
}
```

This produces relative paths such as:

```text
2026-06-11/97847659691b3cae8857/photo.jpg
2026-06-11/97847659691b3cae8857/variants/photo-thumbnail.jpg
```

The database stores the relative path together with the selected storage disk name. The disk configuration decides where the file physically lives.

## Selecting Storage Is Not A Path Generator Responsibility

Do not do this in a path generator:

```php
$basePath = $collection->isProtected() ? WRITEPATH : realpath(ROOTPATH . 'public') . DIRECTORY_SEPARATOR;
```

That couples generated database values to the current application directory. Instead, configure disks:

```php
public array $storages = [
    'public' => [
        'driver'     => 'local',
        'root'       => WRITEPATH . 'asset-connect/public',
        'public_url' => 'assets/storage',
        'visibility' => 'public',
    ],
];
```

Then choose a disk at collection level only when needed:

```php
public function definition(AssetCollectionSetterInterface $definition): void
{
    $definition
        ->setStorage('public')
        ->allowedExtensions('jpg', 'png');
}
```

## Understanding PathGeneratorHelper

The `PathGeneratorHelper` class provides methods for generating unique storage-relative paths:

1. `getUniqueId(bool $moreEntropy = false)`: Generates a unique ID.

   ```php
   $uniqueId = $generatorHelper->getUniqueId();
   ```

2. `getRandomId(int $length = 20)`: Generates a random hex ID suitable for short unique path segments.

   ```php
   $randomId = $generatorHelper->getRandomId();
   ```

3. `getDate()`: Gets the current date formatted as a folder name.

   ```php
   $dateFolder = $generatorHelper->getDate();
   ```

4. `getDateTime()`: Generates a date-time folder string.

   ```php
   $dateTimeFolder = $generatorHelper->getDateTime();
   ```

5. `getTime()`: Gets the current time formatted as a string.

   ```php
   $timeString = $generatorHelper->getTime();
   ```

6. `getPathString(string ...$segments)`: Joins path segments.

   ```php
   $path = $generatorHelper->getPathString('folder1', 'folder2', 'folder3');
   ```

## Understanding AssetCollectionGetterInterface

The `AssetCollectionGetterInterface` is passed to path generator methods and provides collection metadata:

1. `getVisibility()`: Returns `AssetVisibility::PUBLIC` or `AssetVisibility::PROTECTED`.
2. `getStorage()`: Returns the explicitly selected storage disk name, if one was configured.
3. `getMaximumNumberOfItemsInCollection()`: Returns the maximum number of items allowed in the collection.
4. `getMaxFileSize()`: Returns the maximum file size allowed for assets.
5. `isSingleFileCollection()`: Returns whether the collection is limited to one file.
6. `getAllowedMimeTypes()`: Returns allowed MIME types.
7. `getAllowedExtensions()`: Returns allowed file extensions.

## Importance Of Unique Paths

Each stored asset should receive a unique relative path. This prevents uploads with the same file name from overwriting one another.

Use `getRandomId()` and `getDate()` to generate readable collision-resistant paths. A practical structure is:

```text
{date}/{random-id}/{file-name}
```

## Directory Security

Directory permissions, web exposure, and public URLs belong to the storage configuration and server setup, not to path generation. For local storage disks that should have URLs, define `public_url` and expose the configured root with `php spark asset-connect:storage-link` or a web server alias.
