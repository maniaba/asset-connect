<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Config;

use CodeIgniter\Config\BaseService;
use Maniaba\FileConnect\Repositories\AssetRepository;
use Maniaba\FileConnect\Repositories\Interfaces\AssetRepositoryInterface;
use Maniaba\FileConnect\Services\AssetAccessService;
use Maniaba\FileConnect\Services\Interfaces\AssetAccessServiceInterface;

class Services extends BaseService
{
    public static function assetAccessService(?AssetRepositoryInterface $assetRepository = null, bool $getShared = true): AssetAccessServiceInterface
    {
        if ($getShared) {
            return static::getSharedInstance('assetAccessService');
        }

        $assetRepository ??= new AssetRepository();

        return new AssetAccessService($assetRepository);
    }
}
