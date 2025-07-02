<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Config;

use CodeIgniter\Config\BaseService;
use Maniaba\FileConnect\Services\AssetAccessService;
use Maniaba\FileConnect\Services\Interfaces\AssetAccessServiceInterface;

class Services extends BaseService
{
    /**
     * @var array<string, mixed>
     */
    protected static $instances = [];

    public static function assetAccessService(bool $getShared = true): AssetAccessServiceInterface
    {
        if ($getShared) {
            return static::getSharedInstance('assetAccessService');
        }

        return new AssetAccessService();
    }
}
