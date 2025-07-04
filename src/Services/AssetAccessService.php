<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Services;

use CodeIgniter\HTTP\DownloadResponse;
use Maniaba\FileConnect\Asset\Asset;
use Maniaba\FileConnect\Asset\Interfaces\AuthorizableAssetCollectionDefinitionInterface;
use Maniaba\FileConnect\Exceptions\PageException;
use Maniaba\FileConnect\Models\AssetModel;
use Maniaba\FileConnect\Services\Interfaces\AssetAccessServiceInterface;
use Maniaba\FileConnect\UrlGenerator\TempUrlToken;
use Override;

final class AssetAccessService implements AssetAccessServiceInterface
{
    /**
     * {@inheritDoc}
     */
    #[Override]
    public function handleAssetRequest(int $assetId, ?string $variantName = null): DownloadResponse
    {
        // Get the asset from the database
        $assetModel = model(AssetModel::class, false);
        $asset      = $assetModel->find($assetId);

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

        // If a variant is requested, get the variant path
        if ($variantName !== null && $variantName !== '') {
            $variant = $asset->metadata->assetVariant->getAssetVariant($variantName);

            if ($variant === null) {
                // If the variant is not found, throw an exception
                throw PageException::forVariantNotFound($variantName);
            }

            $filePath     = $variant->path;
            $relativePath = $variant->relative_path;
        }

        // Check if the file exists
        if (! file_exists($filePath)) {
            throw PageException::forFileNotFound($relativePath);
        }

        // download the file

        // Create a download response
        $response = new DownloadResponse($asset->file_name, false);
        $response->setFileName($asset->name . (in_array($variantName, [null, ''], true) ? " ({$variantName})" : '') . '.' . $asset->extension);
        $response->setContentType($asset->mime_type);

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
