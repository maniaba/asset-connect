<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\AssetCollection\Interfaces;

use Closure;
use CodeIgniter\Model;
use Maniaba\FileConnect\Asset\Interfaces\AssetCollectionDefinitionInterface;
use Maniaba\FileConnect\Exceptions\InvalidArgumentException;
use Maniaba\FileConnect\PathGenerator\PathGeneratorInterface;

interface SetupAssetCollectionInterface
{
    /**
     * Set the definition of the asset collection for this entity as default.
     *
     * @param AssetCollectionDefinitionInterface|class-string<AssetCollectionDefinitionInterface> $collectionDefinition
     *
     * @throws InvalidArgumentException if the provided class does not implement AssetCollectionInterface
     */
    public function setDefaultCollectionDefinition(AssetCollectionDefinitionInterface|string $collectionDefinition): static;

    /**
     * Set the path generator for the asset collection.
     *
     * @param class-string<PathGeneratorInterface>|PathGeneratorInterface $pathGenerator
     *
     * @throws InvalidArgumentException if the provided class does not implement PathGeneratorInterface
     */
    public function setPathGenerator(PathGeneratorInterface|string $pathGenerator): static;

    /**
     * Set a closure to sanitize file names.
     *
     * @param Closure(string $fileName): string $sanitizer
     */
    public function setFileNameSanitizer(Closure $sanitizer): static;

    /**
     * Set whether to preserve the original file.
     */
    public function setPreserveOriginal(bool $preserve): static;

    /**
     * Set the primary key attribute for the subject of the asset collection. We will try automatically detect it, from the model::$primaryKey;
     *
     * @param string $attribute The name of the primary key attribute, default is 'id'. Example: 'user_id', 'post_id', etc.
     */
    public function setSubjectPrimaryKeyAttribute(string $attribute): static;

    /**
     * Automatically detect the primary key attribute of the subject model.
     *
     * @param class-string<Model> $fromModel The model class to detect the primary key from.
     *
     * @throws InvalidArgumentException If the model class does not exist or is not a subclass of Model.
     */
    public function autoDetectSubjectPrimaryKeyAttribute(string $fromModel): static;
}
