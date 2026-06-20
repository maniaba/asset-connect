<?php

declare(strict_types=1);

namespace Tests\Support\Models;

use Maniaba\AssetConnect\Models\AssetModel;
use Override;

final class RecordingPlatformAssetModel extends AssetModel
{
    /**
     * @var list<array{0: array<string, bool|float|int|string|null>|string, 1: bool|float|int|string|null, 2: bool|null}>
     */
    public array $whereCalls = [];

    private string $databasePlatform = 'SQLite3';

    public function useDatabasePlatform(string $databasePlatform): self
    {
        $this->databasePlatform = $databasePlatform;

        return $this;
    }

    #[Override]
    protected function getDatabasePlatform(): string
    {
        return $this->databasePlatform;
    }

    /**
     * @param array<string, bool|float|int|string|null>|string $key
     * @param bool|float|int|string|null                       $value
     */
    public function where($key, $value = null, ?bool $escape = null): self
    {
        $this->whereCalls[] = [$key, $value, $escape];

        return $this;
    }
}
