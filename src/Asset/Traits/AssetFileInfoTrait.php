<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Asset\Traits;

use CodeIgniter\Files\File;
use CodeIgniter\HTTP\Files\UploadedFile;
use Maniaba\AssetConnect\Utils\Format;

/**
 * Trait providing file information methods for assets.
 *
 * @property      int                    $size                The size of the asset in bytes.
 * @property      File|UploadedFile|null $file                The file associated with the asset (nullable in Asset context, non-nullable in PendingAsset).
 * @property-read string                 $size_human_readable The human-readable size of the asset.
 */
trait AssetFileInfoTrait
{
    public function getHumanReadableSize(int $precision = 2): string
    {
        return Format::formatBytesHumanReadable($this->size, $precision);
    }
}
