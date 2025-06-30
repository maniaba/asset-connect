<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\UrlGenerator;

use CodeIgniter\Entity\Entity;
use CodeIgniter\I18n\Time;
use Maniaba\FileConnect\Asset\Asset;
use Maniaba\FileConnect\AssetCollection\AssetCollectionDefinitionFactory;
use Maniaba\FileConnect\Enums\AssetVisibility;
use Maniaba\FileConnect\Interfaces\Asset\AssetCollectionDefinitionInterface;

final class DefaultUrlGenerator implements UrlGeneratorInterface
{
    private Asset $asset;
    private AssetCollectionDefinitionInterface $collectionDefinition;
    private ?Entity $entity = null;

    public function __construct(Asset $asset, ?Entity $entity = null)
    {
        $this->asset = $asset;
        $this->entity = $entity;
        $this->collectionDefinition = AssetCollectionDefinitionFactory::create($asset->collection);
    }

    /**
     * Check if the entity has authorization to access the asset
     *
     * @param Entity|null $entity The entity to check authorization for, or null to use the one from constructor
     *
     * @return bool True if the entity is authorized, false otherwise
     */
    public function checkAuthorization(?Entity $entity = null): bool
    {
        $entity = $entity ?? $this->entity;

        if ($entity === null) {
            return false;
        }

        return $this->collectionDefinition->checkAuthorization($entity, $this->asset);
    }

    public function getUrl(string $variantName = ''): string
    {
        $collection = AssetCollectionDefinitionFactory::createCollection($this->collectionDefinition);
        $isProtected = $collection->getVisibility() === AssetVisibility::PROTECTED;

        if ($isProtected) {
            // For protected assets, we need to go through a controller that checks authorization
            if ($this->entity !== null && !$this->checkAuthorization()) {
                throw new \Maniaba\FileConnect\Exceptions\AuthorizationException('Entity is not authorized to access this asset.');
            }

            return site_url('assets/view/' . $this->asset->id . ($variantName ? '/' . $variantName : ''));
        }

        // For public assets, we can directly return the URL
        $basePath = 'assets/' . date('Y/m/d', $this->asset->created_at->getTimestamp());
        $filename = $this->asset->file_name;

        if ($variantName) {
            $basePath .= '/variants/' . $variantName;
        }

        return site_url($basePath . '/' . $filename);
    }

    public function getTemporaryUrl(Time $expiration, string $variantName = '', array $options = []): string
    {
        $collection = AssetCollectionDefinitionFactory::createCollection($this->collectionDefinition);
        $isProtected = $collection->getVisibility() === AssetVisibility::PROTECTED;

        // Generate a signed URL that expires at the given time
        $token = $this->generateSignedToken($expiration, $variantName, $options);

        if ($isProtected) {
            // For protected assets, we need to go through a controller that checks authorization
            if ($this->entity !== null && !$this->checkAuthorization()) {
                throw new \Maniaba\FileConnect\Exceptions\AuthorizationException('Entity is not authorized to access this asset.');
            }

            return site_url('assets/temp/' . $this->asset->id . ($variantName ? '/' . $variantName : '') . '?token=' . $token . '&expires=' . $expiration->getTimestamp());
        }

        // For public assets, we can directly return the URL with a token
        $basePath = 'assets/' . date('Y/m/d', $this->asset->created_at->getTimestamp());
        $filename = $this->asset->file_name;

        if ($variantName) {
            $basePath .= '/variants/' . $variantName;
        }

        return site_url($basePath . '/' . $filename . '?token=' . $token . '&expires=' . $expiration->getTimestamp());
    }

    /**
     * Generate a signed token for temporary URLs
     */
    private function generateSignedToken(Time $expiration, string $variantName, array $options): string
    {
        // Create a signature using the asset ID, variant name, expiration time, and a secret key
        $data = $this->asset->id . '|' . $variantName . '|' . $expiration->getTimestamp();

        if (!empty($options)) {
            $data .= '|' . json_encode($options);
        }

        return hash_hmac('sha256', $data, config('Encryption')->key ?? '');
    }

}
