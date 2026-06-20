<?php

declare(strict_types=1);

namespace Tests\Support\Models;

use CodeIgniter\Entity\Entity;
use CodeIgniter\Model;
use Maniaba\AssetConnect\Contracts\AssetConnectModelInterface;
use Maniaba\AssetConnect\Traits\UseAssetConnectModelTrait;

final class InvalidAssetConnectReturnTypeModel extends Model implements AssetConnectModelInterface
{
    use UseAssetConnectModelTrait;

    protected $table      = 'fake_asset_entities';
    protected $primaryKey = 'id';
    protected $returnType = Entity::class;
}
