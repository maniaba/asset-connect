<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Asset\Properties;

use CodeIgniter\Entity\Entity;
use Maniaba\FileConnect\Asset\Interfaces\AssetCollectionDefinitionInterface;
use Maniaba\FileConnect\Asset\Interfaces\AuthorizableAssetCollectionDefinitionInterface;

final class BasicInfoProperty extends BaseProperty
{
    public static function getName(): string
    {
        return 'basic_info';
    }

    public function entityTypeClass(Entity|string $entity): void
    {
        if ($entity instanceof Entity) {
            $entity = $entity::class;
        }

        $this->set('entity_type_class', $entity);
    }

    public function entityTypeClassName(): ?string
    {
        return $this->get('entity_type_class');
    }

    public function collectionClass(AssetCollectionDefinitionInterface|string $collectionClass): void
    {
        if ($collectionClass instanceof AssetCollectionDefinitionInterface) {
            $collectionClass = $collectionClass::class;
        }

        $this->set('collection_class', $collectionClass);
    }

    public function collectionClassName(): ?string
    {
        return $this->get('collection_class');
    }

    /**
     * Check if the collection is protected.
     *
     * A collection is considered protected if it implements the AuthorizableAssetCollectionDefinitionInterface.
     *
     * @return bool True if the collection is protected, false otherwise.
     */
    public function isProtectedCollection(): bool
    {
        $collectionClass = $this->get('collection_class');

        return is_subclass_of($collectionClass, AuthorizableAssetCollectionDefinitionInterface::class);
    }
}
