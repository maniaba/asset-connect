<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Asset\Properties;

use Override;

final class StorageProperty extends BaseProperty
{
    #[Override]
    public static function getName(): string
    {
        return 'storage_info';
    }

    public function setStorageName(string $storage): void
    {
        $this->set('storage', $storage);
    }

    public function storageName(): ?string
    {
        return $this->get('storage');
    }

    public function setFileRelativePath(string $path): void
    {
        $this->set('file_relative_path', $path);
    }

    public function fileRelativePath(): ?string
    {
        return $this->get('file_relative_path');
    }
}
