<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;
use Maniaba\FileConnect\Services\AssetAccessService;

final class AssetConnectController extends Controller
{
    public function show(int $assetId, ?string $variantName = null): ResponseInterface
    {
        // This method should handle the logic to show an asset based on its ID and variant name.
        // The actual implementation will depend on how assets are stored and retrieved in your application.
        /** @var AssetAccessService $service */
        $service = service('assetAccessService');

        if ($variantName === '') {
            $variantName = null; // Handle empty variant name as null
        }

        $response = $service->handleAssetRequest($assetId, $variantName);

        $needsDownload = $this->request->getGet('download') !== null;

        if (! $needsDownload) {
            $response->inline();
        }

        return $response;
    }

    public function temporary(string $token): ResponseInterface
    {
        // This method should handle the logic to show a temporary asset based on a token.
        // The actual implementation will depend on how temporary URLs are generated and validated in your application.
        /** @var AssetAccessService $service */
        $service = service('assetAccessService');

        $response = $service->handleTemporaryAssetRequest($token);

        $needsDownload = $this->request->getGet('download') !== null;

        if (! $needsDownload) {
            $response->inline();
        }

        return $response;
    }
}
