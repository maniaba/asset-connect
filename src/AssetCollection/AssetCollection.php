<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\AssetCollection;

use Maniaba\FileConnect\Asset\Interfaces\AssetCollectionGetterInterface;
use Maniaba\FileConnect\Asset\Interfaces\AssetCollectionSetterInterface;
use Maniaba\FileConnect\Asset\Interfaces\AuthorizableAssetCollectionDefinitionInterface;
use Maniaba\FileConnect\Enums\AssetExtension;
use Maniaba\FileConnect\Enums\AssetMimeType;
use Maniaba\FileConnect\Enums\AssetVisibility;
use Maniaba\FileConnect\Exceptions\InvalidArgumentException;
use Maniaba\FileConnect\PathGenerator\Interfaces\PathGeneratorInterface;
use Maniaba\FileConnect\Utils\PhpIni;
use Override;

final class AssetCollection implements AssetCollectionSetterInterface, AssetCollectionGetterInterface
{
    /**
     * Array of allowed file extensions
     */
    private array $allowedExtensions = [];

    /**
     * Array of allowed MIME types
     */
    private array $allowedMimeTypes = [];

    /**
     * Maximum file size in bytes
     */
    private float|int $maxFileSize = 0;

    private AssetVisibility $visibility = AssetVisibility::PUBLIC;

    /**
     * Maximum number of files
     */
    private int $maximumNumberOfItemsInCollection = 0;

    private PathGeneratorInterface $pathGenerator;

    private function __construct(
        public readonly SetupAssetCollection $setupAssetCollection,
    ) {
        $this->setMaxFileSize(PhpIni::uploadMaxFilesizeBytes());

        // Make changes to the collection definition
        $definition = $this->setupAssetCollection->getCollectionDefinition();

        $definition->definition($this);
        // If the collection implements AuthorizableAssetCollectionDefinitionInterface, use the private path
        if ($definition instanceof AuthorizableAssetCollectionDefinitionInterface) {
            $this->setVisibility(AssetVisibility::PROTECTED);
        }
    }

    #[Override]
    public function setPathGenerator(PathGeneratorInterface $pathGenerator): static
    {
        $this->pathGenerator = $pathGenerator;

        return $this;
    }

    public function getPathGenerator(): PathGeneratorInterface
    {
        if (! isset($this->pathGenerator)) {
            $this->pathGenerator = $this->setupAssetCollection->getPathGenerator();
        }

        return $this->pathGenerator;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function allowedExtensions(AssetExtension|string ...$extensions): static
    {
        $this->allowedExtensions = array_unique(array_map(static function ($extension) {
            if ($extension instanceof AssetExtension) {
                return $extension->value; // Convert enum to string
            }

            if (! preg_match('/^[a-zA-Z0-9]+$/', $extension)) {
                throw new InvalidArgumentException('Invalid file extension: ' . $extension);
            }

            if ($extension === '') {
                throw new InvalidArgumentException('File extension cannot be empty.');
            }

            if ($extension[0] === '.') {
                throw new InvalidArgumentException('File extension should not start with a dot: ' . $extension);
            }

            return strtolower(trim($extension));
        }, $extensions));

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function getAllowedExtensions(): array
    {
        return $this->allowedExtensions;
    }

    #[Override]
    public function singleFileCollection(): static
    {
        $this->onlyKeepLatest(1);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function allowedMimeTypes(AssetMimeType|string ...$mimeTypes): static
    {
        $this->allowedMimeTypes = array_unique(array_map(static function ($mimeType) {
            if ($mimeType instanceof AssetMimeType) {
                return $mimeType->value; // Convert enum to string
            }

            if (! preg_match('/^[\w\-+]+\/[\w\-+.]+$/', $mimeType)) {
                throw new InvalidArgumentException('Invalid MIME type: ' . $mimeType);
            }
            if (trim($mimeType) === '') {
                throw new InvalidArgumentException('MIME type cannot be empty.');
            }

            return strtolower(trim($mimeType));
        }, $mimeTypes));

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function getAllowedMimeTypes(): array
    {
        return $this->allowedMimeTypes;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function setMaxFileSize(float|int $maxFileSize): static
    {
        if ($maxFileSize < 0) {
            throw new InvalidArgumentException('Maximum file size must be a non-negative integer.');
        }

        $this->maxFileSize = $maxFileSize;

        return $this;
    }

    #[Override]
    public function isSingleFileCollection(): bool
    {
        return $this->maximumNumberOfItemsInCollection === 1;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function getMaxFileSize(): int
    {
        return $this->maxFileSize;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function onlyKeepLatest(int $maximumNumberOfItemsInCollection): static
    {
        if ($maximumNumberOfItemsInCollection < 0) {
            throw new InvalidArgumentException('Maximum number of files must be a non-negative integer.');
        }

        $this->maximumNumberOfItemsInCollection = $maximumNumberOfItemsInCollection;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function getMaximumNumberOfItemsInCollection(): int
    {
        return $this->maximumNumberOfItemsInCollection;
    }

    public function setVisibility(AssetVisibility $visibility): static
    {
        $this->visibility = $visibility;

        return $this;
    }

    #[Override]
    public function getVisibility(): AssetVisibility
    {
        return $this->visibility;
    }

    /**
     * Create a new AssetCollection instance.
     *
     * @param SetupAssetCollection $setupAssetCollection The setup asset collection.
     *
     * @throws InvalidArgumentException If the collection definition is not a valid class or interface.
     */
    public static function create(SetupAssetCollection $setupAssetCollection): static
    {
        return new AssetCollection($setupAssetCollection);
    }

    #[Override]
    public function isProtected(): bool
    {
        return $this->getVisibility() === AssetVisibility::PROTECTED;
    }
}
