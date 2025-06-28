<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Interfaces\Asset;

use CodeIgniter\Entity\Entity;
use Maniaba\FileConnect\Asset\Asset;

interface AuthorizableAssetCollectionInterface extends AssetCollectionInterface
{
    /**
     *  Check if the user is authorized to access the asset collection.
     *  Files typically stored in config(Asset)->storagePaths['private'].
     */
    public function checkAuthorization(array|Entity $entity, Asset $asset): bool;
}
