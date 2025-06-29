<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Asset;

use CodeIgniter\Entity\Entity;

/**
 * @property string $name
 * @property string $path
 * @property bool   $processed
 * @property int    $size
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
    ];
}
