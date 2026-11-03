# Installation

## Requirements

Before installing CodeIgniter Asset Connect, ensure your environment meets the following requirements:

- PHP 8.3 or higher
- CodeIgniter 4.6 or higher
- CodeIgniter Queue
- Flysystem 3 and Flysystem Local
- Composer is recommended for dependency resolution, but AssetConnect can be installed manually when the dependencies are already available.

## Installation Steps

### 1. Install the Package

#### Option A: Composer

You can install the package via Composer:

```bash
composer require maniaba/asset-connect
```

#### Option B: Manual Install

Use this option for environments where Composer is not available on the server.

Manual installation only registers AssetConnect itself. The required dependencies still need to be available to the application. The safest deployment flow is to run Composer locally, then upload the generated `vendor/` directory together with your application. If your application already has CodeIgniter Queue, Flysystem, and Flysystem Local available, you can upload only the AssetConnect package files.

1. Download a release archive or clone the repository.
2. Upload the package so the library source is available at:

    ```text
    app/ThirdParty/asset-connect/src
    ```

3. Register the namespace in `app/Config/Autoload.php`:

    ```php
    public $psr4 = [
        APP_NAMESPACE => APPPATH,
        'Maniaba\\AssetConnect' => APPPATH . 'ThirdParty/asset-connect/src',
    ];
    ```

    Alternatively, include the package bootstrap file from the same config:

    ```php
    public $files = [
        APPPATH . 'ThirdParty/asset-connect/autoload.php',
    ];
    ```

    Use the direct `$psr4` mapping or the `autoload.php` file, not both.

4. Keep CodeIgniter module auto-discovery enabled in `app/Config/Modules.php`. The default CodeIgniter configuration already discovers `routes`, `services`, and `registrars`; AssetConnect uses those for protected asset routes, services, and queue job registration.

### 2. Run Migrations

The library includes a migration to create the necessary database table for storing asset metadata. Run the migration using the following command:

```bash
php spark migrate --namespace=Maniaba\\AssetConnect
```

This will create the `assets` table in your database.

If your environment does not allow CLI access, run the migration during deployment from an environment that has CLI access, or create the table using the schema in `src/Database/Migrations/2025-06-18-180653_CreateAssetsTable.php` and keep your migration history consistent.

### 3. Link Local Storage

If you use the default local public storage disk, expose the configured public storage root from your public folder:

```bash
php spark asset-connect:storage-link
```

The command creates links for public local disks that define `public_url`. Protected disks are served through AssetConnect routes and should not be exposed through a public link or web-server alias.

In environments that do not allow symlinks or `php spark`, either create the equivalent public link/alias from the hosting control panel, or configure the public storage disk root directly inside your public web directory. Keep protected storage under `WRITEPATH` or another non-public directory.

### 4. Configure Your Entities

To use Asset Connect with your entities, you need to add the `UseAssetConnectTrait` to any entity you want to associate files with:

```php
<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;
use Maniaba\AssetConnect\Traits\UseAssetConnectTrait;
use Maniaba\AssetConnect\AssetCollection\Interfaces\SetupAssetCollectionInterface;

class User extends Entity
{
    use UseAssetConnectTrait;

    // You must implement this abstract method
    public function setupAssetConnect(SetupAssetCollectionInterface $setup): void
    {
        // Set the default collection definition
        // Note: Only one default collection can be set; additional calls will override previous ones
        $setup->setDefaultCollectionDefinition(ImagesCollection::class);

    }

    // Your other entity methods...
}
```

### 5. Configure Your Models (Optional)

If you want to automatically load the Asset Connect functionality when retrieving entities from your models, you can add the `UseAssetConnectModelTrait` to your models:

```php
<?php

namespace App\Models;

use CodeIgniter\Model;
use Maniaba\AssetConnect\Traits\UseAssetConnectModelTrait;

class UserModel extends Model
{
    use UseAssetConnectModelTrait;

    // Your model configuration...
}
```

If your model already has an `initialize()` method, you need to use PHP's trait aliasing feature to avoid method conflicts:

```php
<?php

namespace App\Models;

use CodeIgniter\Model;
use Maniaba\AssetConnect\Traits\UseAssetConnectModelTrait;

class UserModel extends Model
{
    // Use the trait with method aliasing to avoid conflicts with existing initialize method
    use UseAssetConnectModelTrait {
        initialize as initializeAssetConnectModel;
    }

    protected function initialize(): void
    {
        // Call the trait's initialize method with its new alias
        $this->initializeAssetConnectModel();

        // Your existing initialize code...
        // etc...
    }

    // Your other model methods...
}
```

### 6. Configure Entity and Collection Definitions (Required)

**This is a required step for Asset Connect to function properly.**

You **must** register your entity types and asset collections in the configuration file. Create or extend the `Config\Asset.php` file in your application:

```php
<?php

namespace Config;

use App\Entities\User;
use App\Entities\Product;
use App\AssetCollections\ProfilePicturesCollection;
use App\AssetCollections\ProductImagesCollection;
use Maniaba\AssetConnect\Config\Asset as BaseAssetConfig;

class Asset extends BaseAssetConfig
{
    /**
     * REQUIRED: Define entity types and their unique identifiers
     * Every entity that uses UseAssetConnectTrait must be registered here
     */
    public array $entityKeyDefinitions = [
        User::class => 'user',
        Product::class => 'product',
    ];

    /**
     * REQUIRED: Define collection definitions and their unique identifiers
     * Every asset collection class you create must be registered here
     */
    public array $collectionKeyDefinitions = [
        ProfilePicturesCollection::class => 'profile_pictures',
        ProductImagesCollection::class => 'product_images',
    ];
}
```

**Why this is required:**

- Asset Connect uses these identifiers to store and retrieve asset associations
- Without these definitions, the library cannot identify which entity or collection an asset belongs to
- These mappings are stored in the database and are essential for data integrity
- They enable proper querying and filtering of assets by type

**Important:** You must add every entity and collection to these arrays as you create them. Failure to do so will prevent Asset Connect from working with those entities or collections.

For more details, see the [Configuration](configuration.md) documentation.

## Next Steps

After installation, you may want to:

1. [Configure the library](configuration.md) to customize its behavior
2. Learn about [basic usage](basic-usage.md) to start working with assets
