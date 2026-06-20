<?php

declare(strict_types=1);

namespace Tests\Support\Models;

use Maniaba\AssetConnect\Models\BaseModel;
use Override;

final class ConfiguredBaseModel extends BaseModel
{
    public function configuredDBGroup(): ?string
    {
        return $this->DBGroup;
    }

    #[Override]
    protected function setConfigTableName(): string
    {
        return 'assets';
    }
}
