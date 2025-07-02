<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\AssetVariants;

use CodeIgniter\Entity\Entity;
use Maniaba\FileConnect\Exceptions\FileVariantException;
use stdClass;

/**
 * @property string                                                                          $name
 * @property string                                                                          $path
 * @property array{storage_base_directory_path: string, file_relative_path: string}|stdClass $paths
 * @property bool                                                                            $processed
 * @property int                                                                             $size
 */
final class AssetVariant extends Entity
{
    protected $attributes = [
        'name'      => '',
        'path'      => '',
        'size'      => 0,
        'processed' => false,
    ];
    protected $casts = [
        'size'      => 'int',
        'processed' => 'bool',
        'paths'     => 'json',
    ];

    /**
     * @throws FileVariantException
     */
    public function writeFile(string $data, string $mode = 'wb'): bool
    {
        helper('filesystem');

        if (! write_file($this->path, $data, $mode)) {
            throw new FileVariantException("Failed to write file to path: {$this->path}");
        }

        // Update the size of the variant after writing
        $this->size      = file_exists($this->path) ? filesize($this->path) : 0;
        $this->processed = true;

        return true;
    }
}
