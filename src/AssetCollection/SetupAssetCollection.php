<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\AssetCollection;

use Closure;
use CodeIgniter\Config\BaseConfig;
use Maniaba\FileConnect\Config\Asset;
use Maniaba\FileConnect\Exceptions\AssetException;
use Maniaba\FileConnect\Exceptions\InvalidArgumentException;
use Maniaba\FileConnect\Interfaces\Asset\AssetCollectionInterface;
use Maniaba\FileConnect\Interfaces\AssetCollection\SetupAssetCollection as SetupAssetCollectionInterface;
use Maniaba\FileConnect\PathGenerator\PathGeneratorInterface;

final class SetupAssetCollection implements SetupAssetCollectionInterface
{
    private AssetCollectionInterface $collectionDefinition;
    private PathGeneratorInterface $pathGenerator;

    /**
     * Closure to sanitize file names.
     *
     * @var Closure(string): stringSanitizer
     */
    private Closure $fileNameSanitizer;

    private Asset $config;
    private bool $preserveOriginal = false;

    public function __construct()
    {
        /** @var Asset $config */
        $config = config('Asset');
        if (! $config instanceof BaseConfig) {
            throw new InvalidArgumentException('Asset configuration is not properly set up.');
        }

        $this->config = $config;
    }

    /**
     *  Set the definition of the asset collection for this entity
     */
    public function setCollectionDefinition(AssetCollectionInterface|string $collectionDefinition): static
    {
        if (is_string($collectionDefinition)) {
            if (! class_exists($collectionDefinition) || ! is_subclass_of($collectionDefinition, AssetCollectionInterface::class)) {
                throw new InvalidArgumentException(sprintf(
                    'Expected a class implementing %s, got %s',
                    AssetCollectionInterface::class,
                    $collectionDefinition,
                ));
            }

            $collectionDefinition = new $collectionDefinition();
        }

        $this->collectionDefinition = $collectionDefinition;

        return $this;
    }

    /**
     * Set the path generator for this Entity's asset collection.
     */
    public function setPathGenerator(PathGeneratorInterface $pathGenerator): static
    {
        $this->pathGenerator = $pathGenerator;

        return $this;
    }

    public function getPathGenerator(): PathGeneratorInterface
    {
        if (! isset($this->pathGenerator)) {
            $pathGeneratorClass = $this->config->defaultPathGenerator;

            if (! class_exists($pathGeneratorClass) || ! is_subclass_of($pathGeneratorClass, PathGeneratorInterface::class)) {
                $error = sprintf(
                    'Default path generator class %s does not exist or does not implement %s.',
                    $pathGeneratorClass,
                    PathGeneratorInterface::class,
                );

                throw new InvalidArgumentException($error, $error, 500);
            }

            $this->pathGenerator = new $pathGeneratorClass();
        }

        return $this->pathGenerator;
    }

    public function getCollectionDefinition(): AssetCollectionInterface
    {
        if (! isset($this->collectionDefinition)) {
            $collectionDefinitionClass = $this->config->defaultCollection;

            if (! class_exists($collectionDefinitionClass) || ! is_subclass_of($collectionDefinitionClass, AssetCollectionInterface::class)) {
                $error = sprintf(
                    'Default collection class %s does not exist or does not implement %s.',
                    $collectionDefinitionClass,
                    AssetCollectionInterface::class,
                );

                throw new InvalidArgumentException($error, $error, 500);
            }

            $this->collectionDefinition = new $collectionDefinitionClass();
        }

        return $this->collectionDefinition;
    }

    public function setFileNameSanitizer(Closure $sanitizer): static
    {
        $this->fileNameSanitizer = $sanitizer;

        return $this;
    }

    /**
     * Get the file name sanitizer closure.
     *
     * If no sanitizer is set, it will use the default sanitizer.
     *
     * @return callable(string $fileName):string
     */
    public function getFileNameSanitizer(): Closure
    {
        if (! isset($this->fileNameSanitizer)) {
            $this->fileNameSanitizer = $this->defaultSanitizer(...);
        }

        return $this->fileNameSanitizer;
    }

    private function defaultSanitizer(string $fileName): string
    {
        $sanitizedFileName = preg_replace('#\p{C}+#u', '', $fileName);

        $sanitizedFileName = str_replace(['#', '/', '\\', ' '], '-', $sanitizedFileName);

        $phpExtensions = [
            '.php', '.php3', '.php4', '.php5', '.php7', '.php8', '.phtml', '.phar',
        ];

        foreach ($phpExtensions as $extension) {
            if (str_ends_with(strtolower($sanitizedFileName), $extension)) {
                throw AssetException::forFileNameNotAllowed($sanitizedFileName);
            }
        }

        return $sanitizedFileName;
    }

    public function setPreserveOriginal(bool $preserve): static
    {
        $this->preserveOriginal = $preserve;

        return $this;
    }

    public function shouldPreserveOriginal(): bool
    {
        return $this->preserveOriginal;
    }
}
