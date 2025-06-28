<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Models;

use Maniaba\FileConnect\Asset\Asset;

/**
 * @method Asset|list<Asset>|null find($id = null)
 * @method list<Asset>            findAll(int $limit = 0, int $offset = 0)
 * @method Asset|null             first()
 */
final class AssetModel extends BaseModel
{
    protected $allowedFields = [
        'entity_type', 'entity_id', 'collection', 'name', 'file_name', 'mime_type', 'size', 'path', 'order', 'properties', 'created_at', 'updated_at', 'deleted_at',
    ];
    protected $useSoftDeletes  = true;
    protected $useTimestamps   = true;
    protected $dateFormat      = 'datetime';
    protected $createdField    = 'created_at';
    protected $updatedField    = 'updated_at';
    protected $deletedField    = 'deleted_at';
    protected $returnType      = Asset::class;
    protected $validationRules = [
        'entity_type' => 'required|alpha_numeric_space|max_length[32]',
        'entity_id'   => 'required|integer',
        'collection'  => 'required|alpha_numeric_space|max_length[32]',
        'name'        => 'permit_empty|max_length[255]',
        'file_name'   => 'permit_empty|max_length[255]',
        'mime_type'   => 'permit_empty|max_length[255]',
        'size'        => 'required|integer',
        'path'        => 'required|max_length[1020]',
        'order'       => 'permit_empty|integer',
        'properties'  => 'permit_empty|valid_json|max_length[65535]',
    ];

    protected function setConfigTableName(): string
    {
        return 'assets';
    }
}
