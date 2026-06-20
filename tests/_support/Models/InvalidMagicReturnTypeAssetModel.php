<?php

declare(strict_types=1);

namespace Tests\Support\Models;

use Maniaba\AssetConnect\Models\AssetModel;
use Override;
use stdClass;

final class InvalidMagicReturnTypeAssetModel extends AssetModel
{
    #[Override]
    public function __get(string $name)
    {
        if ($name === 'returnType') {
            return stdClass::class;
        }

        return parent::__get($name);
    }
}
