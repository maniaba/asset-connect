<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Events;

use CodeIgniter\Entity\Entity;
use Maniaba\AssetConnect\Asset\Asset;
use Override;

/**
 * Event fired when an asset is created
 */
final class AssetCreated implements AssetEventInterface
{
    /**
     * Constructor
     *
     * @param Asset $asset The asset that was created
     */
    private function __construct(
        private readonly Asset $asset,
        private readonly Entity $subjectEntity,
    ) {
    }

    #[Override]
    public function getAsset(): Asset
    {
        return $this->asset;
    }

    public function getSubjectEntity(): Entity
    {
        return $this->subjectEntity;
    }

    public static function createFromAsset(Asset $asset, Entity $subjectEntity): self
    {
        return new self($asset, $subjectEntity);
    }

    #[Override]
    public static function name(): string
    {
        return 'asset.created';
    }
}
