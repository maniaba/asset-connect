<?php

declare(strict_types=1);

namespace Tests\Support\Models;

use Maniaba\AssetConnect\Models\AssetModel;
use Override;

final class FailingSaveAssetModel extends AssetModel
{
    #[Override]
    public function save($row): bool
    {
        unset($row);

        return false;
    }

    #[Override]
    public function errors(bool $forceDB = false)
    {
        unset($forceDB);

        return [
            'storage' => 'Unable to update test asset storage.',
        ];
    }
}
