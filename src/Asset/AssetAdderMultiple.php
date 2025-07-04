<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Asset;

use Closure;
use CodeIgniter\Entity\Entity;
use CodeIgniter\HTTP\Files\UploadedFile;
use Maniaba\FileConnect\Asset\Interfaces\AssetCollectionDefinitionInterface;
use Maniaba\FileConnect\Exceptions\InvalidArgumentException;
use Maniaba\FileConnect\Traits\UseAssetConnectTrait;

final readonly class AssetAdderMultiple
{
    public function __construct(
        /** @var array<string, list<UploadedFile>> $uploadedFiles */
        private array $uploadedFiles,
        /** @var Entity&UseAssetConnectTrait $subjectEntity */
        private Entity $subjectEntity,
    ) {
    }

    /**
     * Adds an asset adder to the collection.
     *
     * @param (Closure(UploadedFile $uploadedFile, AssetAdder $assetAdder, int|string $fieldName):void)|null $callback The asset adder to add. First parameter is the uploaded file, second is the asset adder, and third is the field name (post name).
     *
     * @return list<AssetAdder> The updated list of asset adders.
     */
    public function forEach(?Closure $callback = null): array
    {
        $assetAdders = [];

        foreach ($this->uploadedFiles as $fieldName => $uploadedFiles) {
            if (! is_array($uploadedFiles)) {
                $uploadedFiles = [$uploadedFiles];
            }

            foreach ($uploadedFiles as $uploadedFile) {
                if (! $uploadedFile instanceof UploadedFile) {
                    throw new InvalidArgumentException('Expected UploadedFile, got ' . gettype($uploadedFile));
                }

                $assetAdder = $this->subjectEntity->addAsset($uploadedFile);
                if ($callback instanceof Closure) {
                    // Call the callback with the uploaded file and asset adder
                    $callback($uploadedFile, $assetAdder, $fieldName);
                }
                $assetAdders[] = $assetAdder;
            }
        }

        return $assetAdders;
    }

    /**
     * Converts the asset adders to an array of assets.
     *
     * @param AssetCollectionDefinitionInterface|string|null $collection The collection definition or name.
     *
     * @return list<Asset> The array of assets.
     */
    public function toAssetCollection(AssetCollectionDefinitionInterface|string|null $collection = null): array
    {
        $assets = [];

        foreach ($this->uploadedFiles as $uploadedFiles) {
            if (! is_array($uploadedFiles)) {
                $uploadedFiles = [$uploadedFiles];
            }

            foreach ($uploadedFiles as $uploadedFile) {
                $asset    = $this->subjectEntity->addAsset($uploadedFile)->toAssetCollection($collection);
                $assets[] = $asset;
            }
        }

        return $assets;
    }
}
