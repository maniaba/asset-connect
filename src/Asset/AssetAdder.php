<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Asset;

use Closure;
use CodeIgniter\Entity\Entity;
use CodeIgniter\Files\File;
use CodeIgniter\HTTP\Files\UploadedFile;
use Maniaba\AssetConnect\Asset\Interfaces\AssetAdderInterface;
use Maniaba\AssetConnect\Asset\Interfaces\AssetCollectionDefinitionInterface;
use Maniaba\AssetConnect\AssetCollection\SetupAssetCollection;
use Maniaba\AssetConnect\Exceptions\AssetException;
use Maniaba\AssetConnect\Exceptions\FileException;
use Maniaba\AssetConnect\Exceptions\InvalidArgumentException;
use Maniaba\AssetConnect\Traits\UseAssetConnectTrait;
use Override;
use Throwable;

/**
 * Class AssetAdder
 *
 * This class is responsible for handling the asset addition process
 * and storing custom properties and collection information.
 */
final class AssetAdder implements AssetAdderInterface
{
    private Asset $asset;
    private File|UploadedFile $file;
    private Closure $fileNameSanitizer;
    private readonly SetupAssetCollection $setupAssetCollection;

    public function __construct(
        /** @var Entity&UseAssetConnectTrait $subjectEntity The entity to which the asset is being added */
        private readonly Entity $subjectEntity,
        File|string|UploadedFile $file,
    ) {
        // Ensure the entity uses the HasAssetsEntityTrait
        if (! in_array(UseAssetConnectTrait::class, class_uses($this->subjectEntity), true)) {
            throw AssetException::forInvalidEntity($this->subjectEntity);
        }

        // Initialize the SetupAssetCollection instance
        $this->setupAssetCollection = new SetupAssetCollection();
        $this->subjectEntity->setupAssetConnect($this->setupAssetCollection);

        // Get the file name sanitizer from the setup collection "getFileNameSanitizer"
        $this->fileNameSanitizer = $this->setupAssetCollection->getFileNameSanitizer();

        // Set the file for the asset, after setting up the collection
        $this->setFile($file);
    }

    private function setFile(File|string|UploadedFile $file)
    {
        if (is_string($file)) {
            $file = new File($file);
        }

        $fileName = $file instanceof UploadedFile ? $file->getClientName() : $file->getBasename();

        $this->asset = new Asset([
            'file'        => $file,
            'path'        => $file->getRealPath(),
            'file_name'   => $fileName,
            'name'        => pathinfo($fileName, PATHINFO_FILENAME),
            'mime_type'   => $file->getMimeType(),
            'entity_id'   => $this->subjectEntity->{$this->setupAssetCollection->getSubjectPrimaryKeyAttribute()},
            'entity_type' => $this->subjectEntity,
            'size'        => $file->getSize(),
            'order'       => 0, // Default order, can be set later
        ]);

        $this->file = $file;
    }

    /**
     * Sets whether to preserve the original file.
     *
     * @param bool $preserveOriginal Whether to preserve the original file.
     */
    #[Override]
    public function preservingOriginal(bool $preserveOriginal = true): self
    {
        $this->setupAssetCollection->setPreserveOriginal($preserveOriginal);

        return $this;
    }

    /**
     * Sets the order of the asset.
     *
     * @param int $order The order to set for the asset.
     */
    #[Override]
    public function setOrder(int $order): self
    {
        $this->asset->order = $order;

        return $this;
    }

    /**
     * Sets the file name of the asset.
     *
     * @param string $fileName The file name to set for the asset.
     */
    #[Override]
    public function usingFileName(string $fileName): self
    {
        $this->asset->file_name = $fileName;

        return $this;
    }

    /**
     * Sets the name of the asset.
     *
     * @param string $name The name to set for the asset.
     */
    #[Override]
    public function usingName(string $name): self
    {
        $this->asset->name = $name;

        return $this;
    }

    /**
     * Sets a custom file name sanitizer.
     *
     * @param callable(string):string $fileNameSanitizer A callable that takes a string and returns a sanitized string.
     */
    public function sanitizingFileName(callable $fileNameSanitizer): self
    {
        $this->fileNameSanitizer = $fileNameSanitizer;

        return $this;
    }

    /**
     * Adds a custom property to the asset.
     *
     * @param string $key   The key for the custom property.
     * @param mixed  $value The value for the custom property.
     */
    public function withCustomProperty(string $key, mixed $value): self
    {
        $this->asset->metadata->userCustom->set($key, $value);

        return $this;
    }

    /**
     * Adds custom properties to the asset.
     *
     * @param array<string, mixed> $customProperties An associative array of custom properties.
     */
    #[Override]
    public function withCustomProperties(array $customProperties): self
    {
        foreach ($customProperties as $key => $value) {
            $this->asset->metadata->userCustom->set($key, $value);
        }

        return $this;
    }

    /**
     * Store the asset in the specified collection
     *
     * @param AssetCollectionDefinitionInterface|string|null $collection The collection to store the asset in
     *
     * @return Asset The stored asset
     *
     * @throws AssetException|FileException|InvalidArgumentException|Throwable
     */
    #[Override]
    public function toAssetCollection(AssetCollectionDefinitionInterface|string|null $collection = null): Asset
    {
        $this->asset->file_name = call_user_func($this->fileNameSanitizer, (string) $this->asset->file_name);

        if ($collection !== null) {
            $this->setupAssetCollection->setDefaultCollectionDefinition($collection);
        }

        $persistenceManager = new AssetPersistenceManager($this->subjectEntity, $this->asset, $this->setupAssetCollection);

        // Store the asset and return it
        $asset = $persistenceManager->store();

        // Delete the original file if not preserving it
        if (! $this->setupAssetCollection->shouldPreserveOriginal() && ! $this->file instanceof UploadedFile && file_exists($this->file->getRealPath())) {
            @unlink($this->file->getRealPath());
        }

        return $asset;
    }
}
