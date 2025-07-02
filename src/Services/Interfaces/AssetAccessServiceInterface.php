<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Services\Interfaces;

use CodeIgniter\HTTP\ResponseInterface;
use Maniaba\FileConnect\Asset\Asset;

interface AssetAccessServiceInterface
{
    /**
     * Handle a request to access a protected asset
     *
     * @param int         $assetId     The ID of the asset to access
     * @param string|null $variantName Optional variant name
     *
     * @return ResponseInterface The response containing the file or an error
     */
    public function handleAssetRequest(int $assetId, ?string $variantName = null): ResponseInterface;

    /**
     * Habdle a request to access a temporary asset using a token
     *
     * @param string $token The security token for the temporary asset
     *
     * @return ResponseInterface The response containing the file or an error
     */
    public function handleTemporaryAssetRequest(string $token): ResponseInterface;

    /**
     * Check if a user has permission to access an asset
     *
     * @param Asset $asset The asset to check permissions for
     *
     * @return bool True if the user has permission, false otherwise
     */
    public function hasAccessPermission(Asset $asset): bool;
}
