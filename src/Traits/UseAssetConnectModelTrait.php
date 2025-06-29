<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Traits;

use Closure;
use Maniaba\FileConnect\AssetConnect;
use Maniaba\FileConnect\Models\AssetModel;
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

    /**
     * Filter assets associated with this entity using a closure.
     *
     * @param Closure(AssetModel $model): void $filter A closure that takes an Asset and returns a boolean indicating whether to keep the asset.
     */
    public function filterAssets(Closure $filter): static
    {
        $filter($this->assetConnectInstance->assetModel);

        return $this;
    }

    public function assetConnectModel(): AssetModel
    {
        return $this->assetConnectInstance->assetModel;
    }
}
