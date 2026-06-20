<?php

declare(strict_types=1);

namespace Tests\Support\Models;

use Maniaba\AssetConnect\Asset\Asset;
use Maniaba\AssetConnect\Models\AssetModel;
use Override;

final class AssetConnectTestAssetModel extends AssetModel
{
    /**
     * @var list<Asset>
     */
    public static array $findAllReturn = [];

    public static ?Asset $findReturn = null;
    public static bool $deleteReturn = true;

    /**
     * @var array<string, string>
     */
    public static array $errorsReturn = [];

    /**
     * @var list<array{method: string, arguments: array<int, mixed>}>
     */
    public static array $calls = [];

    public static function resetTestState(): void
    {
        self::$findAllReturn = [];
        self::$findReturn    = null;
        self::$deleteReturn  = true;
        self::$errorsReturn  = [];
        self::$calls         = [];
    }

    public function groupStart(): self
    {
        self::recordCall(__FUNCTION__, []);

        return $this;
    }

    public function groupEnd(): self
    {
        self::recordCall(__FUNCTION__, []);

        return $this;
    }

    public function where($key, $value = null, ?bool $escape = null): self
    {
        self::recordCall(__FUNCTION__, [$key, $value, $escape]);

        return $this;
    }

    public function whereIn(?string $key = null, $values = null, ?bool $escape = null): self
    {
        self::recordCall(__FUNCTION__, [$key, $values, $escape]);

        return $this;
    }

    public function orderBy(string $orderBy, string $direction = '', ?bool $escape = null): self
    {
        self::recordCall(__FUNCTION__, [$orderBy, $direction, $escape]);

        return $this;
    }

    public function when(bool $condition, callable $callback): self
    {
        self::recordCall(__FUNCTION__, [$condition, $callback]);

        return $this;
    }

    #[Override]
    public function findAll(?int $limit = null, int $offset = 0)
    {
        self::recordCall(__FUNCTION__, [$limit, $offset]);

        return self::$findAllReturn;
    }

    #[Override]
    public function find($id = null)
    {
        self::recordCall(__FUNCTION__, [$id]);

        if (! is_numeric($id) && ! is_string($id)) {
            return self::$findReturn === null ? [] : [self::$findReturn];
        }

        return self::$findReturn;
    }

    #[Override]
    public function delete($id = null, bool $purge = false): bool
    {
        self::recordCall(__FUNCTION__, [$id, $purge]);

        return self::$deleteReturn;
    }

    #[Override]
    public function errors(bool $forceDB = false)
    {
        self::recordCall(__FUNCTION__, [$forceDB]);

        return self::$errorsReturn;
    }

    /**
     * @param array<int, mixed> $arguments
     */
    private static function recordCall(string $method, array $arguments): void
    {
        self::$calls[] = [
            'method'    => $method,
            'arguments' => $arguments,
        ];
    }
}
