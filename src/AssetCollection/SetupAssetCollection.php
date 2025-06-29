<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\AssetCollection;

use Closure;
use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Model;
use Maniaba\FileConnect\Config\Asset;
use Maniaba\FileConnect\Exceptions\AssetException;
use Maniaba\FileConnect\Exceptions\InvalidArgumentException;
use Maniaba\FileConnect\Interfaces\Asset\AssetCollectionDefinitionInterface;
use Maniaba\FileConnect\Interfaces\AssetCollection\SetupAssetCollection as SetupAssetCollectionInterface;
use Maniaba\FileConnect\PathGenerator\PathGeneratorInterface;

final class SetupAssetCollection implements SetupAssetCollectionInterface
{
    private AssetCollectionDefinitionInterface $collectionDefinition;
    private PathGeneratorInterface $pathGenerator;

    /**
     * Closure to sanitize file names.
     *
     * @var Closure(string): string
     */
    private Closure $fileNameSanitizer;

    private readonly Asset $config;
    private bool $preserveOriginal             = false;
    private string $subjectPrimaryKeyAttribute = 'id';

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
     *
     * * @param AssetCollectionDefinitionInterface|class-string<AssetCollectionDefinitionInterface> $collectionDefinition
     *
     * @throws InvalidArgumentException if the provided class does not implement AssetCollectionDefinitionInterface
     */
    public function setDefaultCollectionDefinition(AssetCollectionDefinitionInterface|string $collectionDefinition, ...$args): static
    {
        if (is_string($collectionDefinition)) {
            $collectionDefinition = AssetCollectionDefinitionFactory::create($collectionDefinition, ...$args);
        }

        $this->collectionDefinition = $collectionDefinition;

        return $this;
    }

    /**
     * Set the path generator for this Entity's asset collection.
     */
    public function setPathGenerator(PathGeneratorInterface|string $pathGenerator): static
    {
        if (is_string(
            $pathGenerator,
        )) {
            if (! class_exists($pathGenerator) || ! is_subclass_of($pathGenerator, PathGeneratorInterface::class)) {
                throw new InvalidArgumentException(sprintf(
                    'Expected a class implementing %s, got %s',
                    PathGeneratorInterface::class,
                    $pathGenerator,
                ));
            }

            $pathGenerator = new $pathGenerator();
        }

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

    /**
     * Get the collection definition for this Entity's asset collection.
     *
     * @throws InvalidArgumentException if the collection definition class does not implement AssetCollectionDefinitionInterface
     */
    public function getCollectionDefinition(): AssetCollectionDefinitionInterface
    {
        if (! isset($this->collectionDefinition)) {
            $this->collectionDefinition = AssetCollectionDefinitionFactory::create($this->config->defaultCollection);
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

    public function setSubjectPrimaryKeyAttribute(string $attribute): static
    {
        $this->subjectPrimaryKeyAttribute = $attribute;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function autoDetectSubjectPrimaryKeyAttribute(string $fromModel): static
    {
        if (! class_exists($fromModel) || ! is_subclass_of($fromModel, Model::class)) {
            throw new InvalidArgumentException(sprintf(
                'Model class %s does not exist.',
                $fromModel,
            ));
        }

        $model = new $fromModel();

        $this->setSubjectPrimaryKeyAttribute($model->primaryKey);

        return $this;
    }

    public function getSubjectPrimaryKeyAttribute(): string
    {
        return $this->subjectPrimaryKeyAttribute;
    }
}
