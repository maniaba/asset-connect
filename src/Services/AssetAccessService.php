<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Services;

use CodeIgniter\HTTP\DownloadResponse;
use Maniaba\AssetConnect\Asset\Asset;
use Maniaba\AssetConnect\Asset\Interfaces\AuthorizableAssetCollectionDefinitionInterface;
use Maniaba\AssetConnect\AssetVariants\AssetVariant;
use Maniaba\AssetConnect\Exceptions\PageException;
use Maniaba\AssetConnect\Pending\PendingAssetManager;
use Maniaba\AssetConnect\Repositories\AssetRepository;
use Maniaba\AssetConnect\Repositories\Interfaces\AssetRepositoryInterface;
use Maniaba\AssetConnect\Services\Interfaces\AssetAccessServiceInterface;
use Maniaba\AssetConnect\Storage\Interfaces\StorageDiskInterface;
use Maniaba\AssetConnect\Storage\StorageManager;
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

        $target = $this->resolveTarget($asset, $variantName);

        if (! $target['disk']->fileExists($target['path'])) {
            throw PageException::forFileNotFound('/' . $target['path']);
        }

        // Create a download response
        $response = $this->createDownloadResponse($target['fileName']);
        $response->setContentType($target['mimeType']);

        $localPath = $target['disk']->localPath($target['path']);
        if ($localPath !== null) {
            $response->setFilePath($localPath);
        } else {
            $response->setBinary($target['disk']->read($target['path']));
        }

        // set size and last modified time
        $response->setHeader('Content-Length', (string) $target['disk']->fileSize($target['path']));
        $response->setHeader('Last-Modified', gmdate('D, d M Y H:i:s', $target['disk']->lastModified($target['path'])) . ' GMT');

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

    public function handlePendingAssetRequest(string $pendingAssetId, ?string $token = null): DownloadResponse
    {
        $pendingAsset = PendingAssetManager::make()->fetchById($pendingAssetId, $token);

        if ($pendingAsset === null) {
            throw PageException::forPendingAssetNotFound($pendingAssetId);
        }

        $sourcePath = $pendingAsset->file->getRealPath();

        if (! is_string($sourcePath) || ! is_file($sourcePath)) {
            throw PageException::forFileNotFound('/pending/' . $pendingAssetId);
        }

        $response = $this->createDownloadResponse($pendingAsset->file_name);
        $response->setContentType($pendingAsset->mime_type);
        $response->setFilePath($sourcePath);
        $response->setHeader('Content-Length', (string) $pendingAsset->size);
        $response->setHeader('Last-Modified', gmdate('D, d M Y H:i:s', $pendingAsset->updated_at->getTimestamp()) . ' GMT');

        return $response;
    }

    /**
     * @return array{disk: StorageDiskInterface, path: string, fileName: string, mimeType: string}
     */
    private function resolveTarget(Asset $asset, ?string $variantName): array
    {
        $storage  = $asset->storage;
        $path     = $asset->path;
        $fileName = $asset->name . '.' . $asset->extension;
        $mimeType = $asset->mime_type;

        if ($variantName !== null && $variantName !== '') {
            $variant = $asset->metadata->assetVariant->getAssetVariant($variantName);

            if (! $variant instanceof AssetVariant) {
                throw PageException::forVariantNotFound($variantName);
            }

            $storage  = $variant->storage;
            $path     = $variant->path;
            $fileName = "{$asset->name}-{$variantName}.{$variant->extension}";
            $mimeType = $variant->mime_type;
        }

        return [
            'disk'     => StorageManager::make()->disk($storage),
            'path'     => $path,
            'fileName' => $fileName,
            'mimeType' => $mimeType,
        ];
    }

    private function createDownloadResponse(string $fileName): DownloadResponse
    {
        return new DownloadResponse($fileName, false);
    }
}
