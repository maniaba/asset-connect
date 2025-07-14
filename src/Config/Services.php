<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Config;

use CodeIgniter\Config\BaseService;
use Maniaba\AssetConnect\Repositories\AssetRepository;
use Maniaba\AssetConnect\Repositories\Interfaces\AssetRepositoryInterface;
use Maniaba\AssetConnect\Services\AssetAccessService;
use Maniaba\AssetConnect\Services\Interfaces\AssetAccessServiceInterface;

class Services extends BaseService
{
    public static function assetAccessService(?AssetRepositoryInterface $assetRepository = null, bool $getShared = true): AssetAccessServiceInterface
    {
        if ($getShared) {
            return static::getSharedInstance('assetAccessService', $assetRepository);
        }

        $assetRepository ??= new AssetRepository();

        return new AssetAccessService($assetRepository);
    }
}
