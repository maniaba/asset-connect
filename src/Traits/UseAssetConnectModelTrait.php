<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Traits;

use Maniaba\FileConnect\AssetConnect;
use RuntimeException;

trait UseAssetConnectModelTrait
{
    private AssetConnect $assetConnectInstance;

    protected function initialize(): void
    {
        // return type must be Entity and must use trait UseAssetConnectTrait
        if (! class_exists($this->returnType) || ! in_array(UseAssetConnectTrait::class, class_uses($this->returnType), true)) {
            throw new RuntimeException('The return type must be an Entity and must use the UseAssetConnectTrait trait.');
        }

        $this->assetConnectInstance = new AssetConnect();
        $this->afterFind[]          = 'triggerModelAfterFindAssetConnect';

        parent::initialize();
    }

    protected function triggerModelAfterFindAssetConnect(array $data): array
    {
        return $this->assetConnectInstance->triggerModelAfterFind($data);
    }
}
