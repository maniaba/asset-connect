<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Asset\Interfaces;

use Maniaba\AssetConnect\Asset\Asset;

interface AuthorizableAssetCollectionDefinitionInterface extends AssetCollectionDefinitionInterface
{
    /**
     *  Check if the user is authorized to access the asset collection.
     *  Files typically stored in this collection are user-specific, such as profile pictures or documents.
     */
    public function checkAuthorization(Asset $asset): bool;
}
