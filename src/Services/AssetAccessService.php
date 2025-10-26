<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Services;

use CodeIgniter\HTTP\DownloadResponse;
use Maniaba\AssetConnect\Asset\Asset;
use Maniaba\AssetConnect\Asset\Interfaces\AuthorizableAssetCollectionDefinitionInterface;
use Maniaba\AssetConnect\Exceptions\PageException;
use Maniaba\AssetConnect\Repositories\AssetRepository;
use Maniaba\AssetConnect\Repositories\Interfaces\AssetRepositoryInterface;
use Maniaba\AssetConnect\Services\Interfaces\AssetAccessServiceInterface;
use Maniaba\AssetConnect\UrlGenerator\TempUrlToken;
use Override;

final readonly class AssetAccessService implements AssetAccessServiceInterface
{
    public function __construct(
        private AssetRepositoryInterface $assetRepository = new AssetRepository(),
    ) {
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function handleAssetRequest(int $assetId, ?string $variantName = null): DownloadResponse
    {
        // Get the asset from the repository
        $asset = $this->assetRepository->find($assetId);

        if ($asset === null) {
            throw PageException::forPageNotFound();
        }

        // Check if the user has permission to access this asset
        if (! $this->hasAccessPermission($asset)) {
            throw PageException::forForbiddenAccess();
        }

        // Get the file path
        $filePath     = $asset->path;
        $relativePath = $asset->relative_path;
        $fileName     = $asset->name . '.' . $asset->extension;
        $mimeType     = $asset->mime_type;

        // If a variant is requested, get the variant path
        if ($variantName !== null && $variantName !== '') {
            $variant = $asset->metadata->assetVariant->getAssetVariant($variantName);

            if ($variant === null) {
                // If the variant is not found, throw an exception
                throw PageException::forVariantNotFound($variantName);
            }

            $filePath     = $variant->path;
            $relativePath = $variant->relative_path;
            $fileName     = "{$asset->name}-{$variantName}.{$variant->extension}";
            $mimeType     = $variant->mime_type;
        }

        // Check if the file exists
        if (! file_exists($filePath)) {
            throw PageException::forFileNotFound($relativePath);
        }

        // download the file

        // Create a download response
        $response = new DownloadResponse($fileName, false);
        $response->setContentType($mimeType);

        $response->setFilePath($filePath);

        // set size and last modified time
        $response->setHeader('Content-Length', (string) filesize($filePath));
        $response->setHeader('Last-Modified', gmdate('D, d M Y H:i:s', filemtime($filePath)) . ' GMT');

        return $response;
    }

    #[Override]
    public function hasAccessPermission(Asset $asset): bool
    {
        $collection = $asset->getAssetCollectionDefinition();

        if (! $collection instanceof AuthorizableAssetCollectionDefinitionInterface) {
            // If the collection does not implement the authorizable interface, allow access
            return true;
        }

        // Check if the user has permission to access this asset
        return $collection->checkAuthorization($asset);
    }

    #[Override]
    public function handleTemporaryAssetRequest(string $token): DownloadResponse
    {
        $tokenData = TempUrlToken::validateToken($token);

        if ($tokenData === null) {
            throw PageException::forForbiddenAccess(lang('Auth.exceptions.token_invalid'));
        }

        // If a token is provided, validate it and get the asset ID and variant from the token

        $assetId     = $tokenData['asset_id'] ?? 0;
        $variantName = $tokenData['variant'] ?? null;

        return $this->handleAssetRequest($assetId, $variantName);
    }
}
