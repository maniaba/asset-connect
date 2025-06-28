<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Entities;

use Closure;
use CodeIgniter\Files\File;
use Maniaba\FileConnect\Asset\Asset;
use Maniaba\FileConnect\AssetConnect;
use RuntimeException;

/**
 * Class FileAdder
 *
 * This class is responsible for handling the asset addition process
 * and storing custom properties and collection information.
 * It is inspired by the FileAdder class from the spatie/laravel-medialibrary package.
 */
class FileAdder
{
    /**
     * @var object The entity to add the asset to
     */
    protected object $subject;

    /**
     * @var bool Whether to preserve the original file
     */
    protected bool $preserveOriginal = false;

    /**
     * @var File|string The file to add as an asset
     */
    protected File|string $file;

    /**
     * @var array Custom properties to store with the asset
     */
    protected array $customProperties = [];

    /**
     * @var array Manipulations to apply to the asset
     */
    protected array $manipulations = [];

    /**
     * @var string The path to the file
     */
    protected string $pathToFile = '';

    /**
     * @var string The file name
     */
    protected string $fileName = '';

    /**
     * @var string The asset name
     */
    protected string $assetName = '';

    /**
     * @var string The disk name
     */
    protected string $diskName = '';

    /**
     * @var int|null The file size
     */
    protected ?int $fileSize = null;

    /**
     * @var Closure|null The file name sanitizer
     */
    protected ?Closure $fileNameSanitizer = null;

    /**
     * @var array Custom headers
     */
    protected array $customHeaders = [];

    /**
     * @var int|null The order
     */
    public ?int $order = null;

    /**
     * @var AssetConnect The asset connect instance
     */
    protected AssetConnect $assetConnect;

    /**
     * Constructor
     *
     * @param object      $subject The entity to add the asset to
     * @param File|string $file    The file to add as an asset
     */
    public function __construct(object $subject, File|string $file)
    {
        $this->subject           = $subject;
        $this->assetConnect      = new AssetConnect();
        $this->fileNameSanitizer = fn ($fileName) => $this->defaultSanitizer($fileName);
        $this->setFile($file);
    }

    /**
     * Set the file to add as an asset
     *
     * @param File|string $file The file to add as an asset
     */
    public function setFile(File|string $file): self
    {
        $this->file = $file;

        if (is_string($file)) {
            $this->pathToFile = $file;
            $this->setFileName(pathinfo($file, PATHINFO_BASENAME));
            $this->assetName = pathinfo($file, PATHINFO_FILENAME);

            return $this;
        }

        $this->pathToFile = $file->getPathname();
        $this->setFileName($file->getBasename());
        $this->assetName = pathinfo($file->getBasename(), PATHINFO_FILENAME);

        return $this;
    }

    /**
     * Set whether to preserve the original file
     *
     * @param bool $preserveOriginal Whether to preserve the original file
     */
    public function preservingOriginal(bool $preserveOriginal = true): self
    {
        $this->preserveOriginal = $preserveOriginal;

        return $this;
    }

    /**
     * Set the asset name
     *
     * @param string $name The asset name
     */
    public function usingName(string $name): self
    {
        return $this->setName($name);
    }

    /**
     * Set the asset name
     *
     * @param string $name The asset name
     */
    public function setName(string $name): self
    {
        $this->assetName = $name;

        return $this;
    }

    /**
     * Set the order
     *
     * @param int|null $order The order
     */
    public function setOrder(?int $order): self
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Set the file name
     *
     * @param string $fileName The file name
     */
    public function usingFileName(string $fileName): self
    {
        return $this->setFileName($fileName);
    }

    /**
     * Set the file name
     *
     * @param string $fileName The file name
     */
    public function setFileName(string $fileName): self
    {
        $this->fileName = $fileName;

        return $this;
    }

    /**
     * Set the file size
     *
     * @param int $fileSize The file size
     */
    public function setFileSize(int $fileSize): self
    {
        $this->fileSize = $fileSize;

        return $this;
    }

    /**
     * Set the custom properties
     *
     * @param array $customProperties The custom properties
     */
    public function withCustomProperties(array $customProperties): self
    {
        $this->customProperties = $customProperties;

        return $this;
    }

    /**
     * Set the disk name
     *
     * @param string $diskName The disk name
     */
    public function storingOnDisk(string $diskName): self
    {
        $this->diskName = $diskName;

        return $this;
    }

    /**
     * Set the manipulations
     *
     * @param array $manipulations The manipulations
     */
    public function withManipulations(array $manipulations): self
    {
        $this->manipulations = $manipulations;

        return $this;
    }

    /**
     * Set the custom headers
     *
     * @param array $customHeaders The custom headers
     */
    public function addCustomHeaders(array $customHeaders): self
    {
        $this->customHeaders = $customHeaders;

        return $this;
    }

    /**
     * Add the asset to a collection
     *
     * @param string $collectionName The collection name
     * @param string $diskName       The disk name
     *
     * @return Asset The created asset
     */
    public function toMediaCollection(string $collectionName = 'default', string $diskName = ''): Asset
    {
        if ($diskName !== '') {
            $this->storingOnDisk($diskName);
        }

        $sanitizedFileName = ($this->fileNameSanitizer)($this->fileName);
        $fileName          = $this->appendExtension(pathinfo($sanitizedFileName, PATHINFO_FILENAME), pathinfo($sanitizedFileName, PATHINFO_EXTENSION));
        $this->fileName    = $fileName;

        if (! is_file($this->pathToFile)) {
            throw new RuntimeException("File does not exist: {$this->pathToFile}");
        }

        $this->fileSize ??= filesize($this->pathToFile);

        $maxFileSize = config('Asset')->maxFileSize ?? 1024 * 1024 * 10; // Default to 10MB
        if ($this->fileSize > $maxFileSize) {
            throw new RuntimeException("File is too big: {$this->pathToFile}");
        }

        $asset = $this->assetConnect->addAssetToEntity(
            $this->subject,
            $this->file,
            $this->customProperties,
            $collectionName,
            $this->diskName,
            $this->manipulations,
            $this->customHeaders,
        );

        if (! $this->preserveOriginal) {
            if (file_exists($this->pathToFile)) {
                unlink($this->pathToFile);
            }
        }

        return $asset;
    }

    /**
     * Add the asset to a collection (alias for toMediaCollection)
     *
     * @param string $collectionName The collection name
     * @param string $diskName       The disk name
     *
     * @return Asset The created asset
     */
    public function toCollection(string $collectionName = 'default', string $diskName = ''): Asset
    {
        return $this->toMediaCollection($collectionName, $diskName);
    }

    /**
     * Sanitize a file name
     *
     * @param string $fileName The file name to sanitize
     *
     * @return string The sanitized file name
     */
    public function defaultSanitizer(string $fileName): string
    {
        // Remove any characters that are not alphanumeric, dash, underscore, or dot
        $sanitizedFileName = preg_replace('/[^a-zA-Z0-9\-_\.]/', '-', $fileName);

        // Replace multiple dashes with a single dash
        $sanitizedFileName = preg_replace('/-+/', '-', $sanitizedFileName);

        // Remove leading and trailing dashes
        $sanitizedFileName = trim($sanitizedFileName, '-');

        // Check for PHP extensions
        $phpExtensions = [
            '.php', '.php3', '.php4', '.php5', '.php7', '.php8', '.phtml', '.phar',
        ];

        foreach ($phpExtensions as $extension) {
            if (strtolower(substr($sanitizedFileName, -strlen($extension))) === $extension) {
                throw new RuntimeException("File name not allowed: {$fileName}");
            }
        }

        return $sanitizedFileName;
    }

    /**
     * Set the file name sanitizer
     *
     * @param callable $fileNameSanitizer The file name sanitizer
     */
    public function sanitizingFileName(callable $fileNameSanitizer): self
    {
        $this->fileNameSanitizer = $fileNameSanitizer;

        return $this;
    }

    /**
     * Append an extension to a file name
     *
     * @param string      $file      The file name
     * @param string|null $extension The extension
     *
     * @return string The file name with extension
     */
    protected function appendExtension(string $file, ?string $extension): string
    {
        return $extension
            ? $file . '.' . $extension
            : $file;
    }

    /**
     * Magic method to convert the FileAdder to an Asset when needed
     *
     * @return Asset The created asset
     */
    public function __toString(): string
    {
        return (string) $this->toMediaCollection();
    }
}
