<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Asset\Traits;

use Maniaba\FileConnect\Enums\AssetMimeType;

trait AssetMimeTypeTrait
{
    /**
     * Get the MIME type of the asset
     *
     * This method should be implemented by the class using this trait to return the MIME type of the asset.
     *
     * @return string The MIME type of the asset
     */
    abstract protected function mimeTypeValue(): string;

    /**
     * Check if the asset is an image
     *
     * @return bool True if the asset is an image, false otherwise
     */
    public function isImage(): bool
    {
        return AssetMimeType::isImage($this->mimeTypeValue());
    }

    /**
     * Check if the asset is a document
     *
     * @return bool True if the asset is a document, false otherwise
     */
    public function isDocument(): bool
    {
        return AssetMimeType::isDocument($this->mimeTypeValue());
    }

    /**
     * Check if the asset is a video
     *
     * @return bool True if the asset is a video, false otherwise
     */
    public function isVideo(): bool
    {
        return AssetMimeType::isVideo($this->mimeTypeValue());
    }

    /**
     * Check if the asset is an audio
     *
     * @return bool True if the asset is an audio, false otherwise
     */
    public function isAudio(): bool
    {
        return AssetMimeType::isAudio($this->mimeTypeValue());
    }

    /**
     * Check if the asset is an archive
     *
     * @return bool True if the asset is an archive, false otherwise
     */
    public function isArchive(): bool
    {
        return AssetMimeType::isArchive($this->mimeTypeValue());
    }

    /**
     * Check if the asset is a text file
     *
     * @return bool True if the asset is a text file, false otherwise
     */
    public function isText(): bool
    {
        return AssetMimeType::isText($this->mimeTypeValue());
    }

    /**
     * Check if the asset is a web file
     *
     * @return bool True if the asset is a web file, false otherwise
     */
    public function isWeb(): bool
    {
        return AssetMimeType::isWeb($this->mimeTypeValue());
    }

    /**
     * Check if the asset is a programming file
     *
     * @return bool True if the asset is a programming file, false otherwise
     */
    public function isProgramming(): bool
    {
        return AssetMimeType::isProgramming($this->mimeTypeValue());
    }

    /**
     * Check if the asset is a font
     *
     * @return bool True if the asset is a font, false otherwise
     */
    public function isFont(): bool
    {
        return AssetMimeType::isFont($this->mimeTypeValue());
    }

    /**
     * Check if the asset is a design file
     *
     * @return bool True if the asset is a design file, false otherwise
     */
    public function isDesign(): bool
    {
        return AssetMimeType::isDesign($this->mimeTypeValue());
    }

    /**
     * Check if the asset is a database file
     *
     * @return bool True if the asset is a database file, false otherwise
     */
    public function isDatabase(): bool
    {
        return AssetMimeType::isDatabase($this->mimeTypeValue());
    }

    /**
     * Check if the asset is an ebook
     *
     * @return bool True if the asset is an ebook, false otherwise
     */
    public function isEbook(): bool
    {
        return AssetMimeType::isEbook($this->mimeTypeValue());
    }

    /**
     * Check if the asset is a CAD file
     *
     * @return bool True if the asset is a CAD file, false otherwise
     */
    public function isCad(): bool
    {
        return AssetMimeType::isCad($this->mimeTypeValue());
    }

    /**
     * Check if the asset is a scientific file
     *
     * @return bool True if the asset is a scientific file, false otherwise
     */
    public function isScientific(): bool
    {
        return AssetMimeType::isScientific($this->mimeTypeValue());
    }

    /**
     * Check if the asset is a configuration file
     *
     * @return bool True if the asset is a configuration file, false otherwise
     */
    public function isConfiguration(): bool
    {
        return AssetMimeType::isConfiguration($this->mimeTypeValue());
    }

    /**
     * Check if the asset is an executable
     *
     * @return bool True if the asset is an executable, false otherwise
     */
    public function isExecutable(): bool
    {
        return AssetMimeType::isExecutable($this->mimeTypeValue());
    }

    /**
     * Check if the asset is a vector graphic
     *
     * @return bool True if the asset is a vector graphic, false otherwise
     */
    public function isVectorGraphic(): bool
    {
        return AssetMimeType::isVectorGraphic($this->mimeTypeValue());
    }

    /**
     * Check if the asset is a raster graphic
     *
     * @return bool True if the asset is a raster graphic, false otherwise
     */
    public function isRasterGraphic(): bool
    {
        return AssetMimeType::isRasterGraphic($this->mimeTypeValue());
    }

    /**
     * Check if the asset is a spreadsheet
     *
     * @return bool True if the asset is a spreadsheet, false otherwise
     */
    public function isSpreadsheet(): bool
    {
        return AssetMimeType::isSpreadsheet($this->mimeTypeValue());
    }

    /**
     * Check if the asset is a presentation
     *
     * @return bool True if the asset is a presentation, false otherwise
     */
    public function isPresentation(): bool
    {
        return AssetMimeType::isPresentation($this->mimeTypeValue());
    }
}
