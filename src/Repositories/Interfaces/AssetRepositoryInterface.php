<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Repositories\Interfaces;

use Maniaba\FileConnect\Asset\Asset;

interface AssetRepositoryInterface
{
    /**
     * Find an asset by its ID
     *
     * @param int $id The asset ID
     *
     * @return Asset|null The asset if found, null otherwise
     */
    public function find(int $id): ?Asset;
}
