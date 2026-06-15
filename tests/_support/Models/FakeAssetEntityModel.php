<?php

declare(strict_types=1);

namespace Tests\Support\Models;

use CodeIgniter\Model;
use Maniaba\AssetConnect\Contracts\AssetConnectModelInterface;
use Maniaba\AssetConnect\Traits\UseAssetConnectModelTrait;
use Tests\Support\Entities\FakeAssetEntity;

/**
 * @method FakeAssetEntity|list<FakeAssetEntity>|null find($id = null)
 * @method list<FakeAssetEntity>                      findAll(int $limit = 0, int $offset = 0)
 */
final class FakeAssetEntityModel extends Model implements AssetConnectModelInterface
{
    use UseAssetConnectModelTrait;

    protected $table         = 'fake_asset_entities';
    protected $primaryKey    = 'id';
    protected $returnType    = FakeAssetEntity::class;
    protected $allowedFields = [
        'title',
    ];
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}
