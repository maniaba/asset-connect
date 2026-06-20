<?php

declare(strict_types=1);

namespace Tests\Support\Pending;

use Maniaba\AssetConnect\Pending\Interfaces\PendingSecurityTokenInterface;
use Maniaba\AssetConnect\Pending\PendingAsset;

final class FakePendingSecurityToken implements PendingSecurityTokenInterface
{
    public static string $generatedToken  = 'fake-pending-token';
    public static ?string $retrievedToken = null;
    public static bool $validateResult    = true;

    /**
     * @var list<string>
     */
    public static array $generatedFor = [];

    /**
     * @var list<array{id: string, token: string|null}>
     */
    public static array $validated = [];

    /**
     * @var list<string>
     */
    public static array $deleted = [];

    public static function reset(): void
    {
        self::$generatedToken = 'fake-pending-token';
        self::$retrievedToken = null;
        self::$validateResult = true;
        self::$generatedFor   = [];
        self::$validated      = [];
        self::$deleted        = [];
    }

    public function generateToken(string $pendingId): string
    {
        self::$generatedFor[] = $pendingId;

        return self::$generatedToken;
    }

    public function retrieveToken(string $pendingId): ?string
    {
        unset($pendingId);

        return self::$retrievedToken;
    }

    public function validateToken(PendingAsset $pendingAsset, ?string $tokenProvided = null): bool
    {
        self::$validated[] = [
            'id'    => $pendingAsset->id,
            'token' => $tokenProvided,
        ];

        return self::$validateResult;
    }

    public function deleteToken(string $pendingId): void
    {
        self::$deleted[] = $pendingId;
    }
}
