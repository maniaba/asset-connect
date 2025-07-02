<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\DownloadResponse;
use CodeIgniter\HTTP\ResponseInterface;
use Maniaba\FileConnect\Services\AssetAccessService;

final class AssetConnectController extends Controller
{
    public function show(int $assetId, ?string $variantName = null, ?string $path = null): ResponseInterface
    {
        // This method should handle the logic to show an asset based on its ID and variant name.
        // The actual implementation will depend on how assets are stored and retrieved in your application.
        /** @var AssetAccessService $service */
        $service = service('assetAccessService');

        $a = $service->handleAssetRequest($assetId, $variantName, $path);

        if ($a instanceof DownloadResponse) {
            return $a->inline();
        }

        return $a;
    }

    public function temporary(string $token): ResponseInterface
    {
        // This method should handle the logic to show a temporary asset based on a token.
        // The actual implementation will depend on how temporary URLs are generated and validated in your application.
        /** @var AssetAccessService $service */
        $service = service('assetAccessService');

        return $service->handleTemporaryAssetRequest($token);
    }
}
