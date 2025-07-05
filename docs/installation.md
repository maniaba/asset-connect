# Installation

## Requirements

Before installing CodeIgniter Asset Connect, ensure your environment meets the following requirements:

- PHP 8.1 or higher
- CodeIgniter 4.3 or higher
- CodeIgniter Queue
- Composer

## Installation Steps

### 1. Install via Composer

You can install the package via Composer:

```bash
composer require maniaba/asset-connect
```

### 2. Run Migrations

The library includes a migration to create the necessary database table for storing asset metadata. Run the migration using the following command:

```bash
php spark migrate --namespace=Maniaba\\AssetConnect
```

This will create the `assets` table in your database.

### 3. Configure Your Entities

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

### 4. Configure Your Models (Optional)

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

## Next Steps

After installation, you may want to:

1. [Configure the library](configuration.md) to customize its behavior
2. Learn about [basic usage](basic-usage.md) to start working with assets
