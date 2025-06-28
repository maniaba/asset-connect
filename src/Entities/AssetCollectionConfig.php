<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Entities;

use InvalidArgumentException;

class AssetCollectionConfig
{
    /**
     * @var string The disk name to store files on
     */
    public string $diskName = '';

    /**
     * @var string The disk name to store conversions on
     */
    public string $conversionsDiskName = '';

    /**
     * @var callable The media conversion registrations
     */
    public $mediaConversionRegistrations;

    /**
     * @var bool Whether to generate responsive images
     */
    public bool $generateResponsiveImages = false;

    /**
     * @var callable The file acceptance callable
     */
    public $acceptsFile;

    /**
     * @var array The accepted MIME types
     */
    public array $acceptsMimeTypes = [];

    /**
     * @var bool|int The collection size limit
     */
    public $collectionSizeLimit = false;

    /**
     * @var bool Whether the collection can only contain a single file
     */
    public bool $singleFile = false;

    /**
     * @var array<string, string> The fallback URLs
     */
    public array $fallbackUrls = [];

    /**
     * @var array<string, string> The fallback paths
     */
    public array $fallbackPaths = [];

    /**
     * Constructor
     *
     * @param string $name The name of the collection
     */
    public function __construct(
        public string $name,
    ) {
        $this->mediaConversionRegistrations = static function () {};
        $this->acceptsFile                  = static fn () => true;
    }

    /**
     * Create a new collection
     *
     * @param string $name The name of the collection
     *
     * @return self The collection
     */
    public static function create(string $name): self
    {
        return new static($name);
    }

    /**
     * Set the disk name
     *
     * @param string $diskName The disk name
     *
     * @return $this
     */
    public function useDisk(string $diskName): self
    {
        $this->diskName = $diskName;

        return $this;
    }

    /**
     * Set the conversions disk name
     *
     * @param string $conversionsDiskName The conversions disk name
     *
     * @return $this
     */
    public function storeConversionsOnDisk(string $conversionsDiskName): self
    {
        $this->conversionsDiskName = $conversionsDiskName;

        return $this;
    }

    /**
     * Set the file acceptance callable
     *
     * @param callable $acceptsFile The file acceptance callable
     *
     * @return $this
     */
    public function acceptsFile(callable $acceptsFile): self
    {
        $this->acceptsFile = $acceptsFile;

        return $this;
    }

    /**
     * Set the accepted MIME types
     *
     * @param array $mimeTypes The accepted MIME types
     *
     * @return $this
     */
    public function acceptsMimeTypes(array $mimeTypes): self
    {
        $this->acceptsMimeTypes = $mimeTypes;

        return $this;
    }

    /**
     * Set the collection to only accept a single file
     *
     * @return $this
     */
    public function singleFile(): self
    {
        return $this->onlyKeepLatest(1);
    }

    /**
     * Set the collection to only keep the latest N items
     *
     * @param int $maximumNumberOfItemsInCollection The maximum number of items in the collection
     *
     * @return $this
     */
    public function onlyKeepLatest(int $maximumNumberOfItemsInCollection): self
    {
        if ($maximumNumberOfItemsInCollection < 1) {
            throw new InvalidArgumentException("You should pass a value higher than 0. `{$maximumNumberOfItemsInCollection}` given.");
        }

        $this->singleFile = ($maximumNumberOfItemsInCollection === 1);

        $this->collectionSizeLimit = $maximumNumberOfItemsInCollection;

        return $this;
    }

    /**
     * Register media conversions
     *
     * @param callable $mediaConversionRegistrations The media conversion registrations
     */
    public function registerMediaConversions(callable $mediaConversionRegistrations): void
    {
        $this->mediaConversionRegistrations = $mediaConversionRegistrations;
    }

    /**
     * Set a fallback URL
     *
     * @param string $url            The fallback URL
     * @param string $conversionName The conversion name
     *
     * @return $this
     */
    public function useFallbackUrl(string $url, string $conversionName = ''): self
    {
        if ($conversionName === '') {
            $conversionName = 'default';
        }

        $this->fallbackUrls[$conversionName] = $url;

        return $this;
    }

    /**
     * Set a fallback path
     *
     * @param string $path           The fallback path
     * @param string $conversionName The conversion name
     *
     * @return $this
     */
    public function useFallbackPath(string $path, string $conversionName = ''): self
    {
        if ($conversionName === '') {
            $conversionName = 'default';
        }

        $this->fallbackPaths[$conversionName] = $path;

        return $this;
    }

    /**
     * Enable responsive images
     *
     * @return $this
     */
    public function withResponsiveImages(): self
    {
        $this->generateResponsiveImages = true;

        return $this;
    }

    /**
     * Enable responsive images conditionally
     *
     * @param mixed $condition The condition
     *
     * @return $this
     */
    public function withResponsiveImagesIf($condition): self
    {
        $this->generateResponsiveImages = (bool) (is_callable($condition) ? $condition() : $condition);

        return $this;
    }
}
