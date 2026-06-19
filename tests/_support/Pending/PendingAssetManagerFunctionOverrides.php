<?php

declare(strict_types=1);

namespace Tests\Support\Pending {
    final class PendingAssetManagerFunctionOverrides
    {
        public static bool $failNextCopy         = false;
        public static bool $failNextTempnam      = false;
        public static ?string $lastTemporaryPath = null;

        public static function reset(): void
        {
            self::$failNextCopy      = false;
            self::$failNextTempnam   = false;
            self::$lastTemporaryPath = null;
        }
    }
}

namespace Maniaba\AssetConnect\Pending {
    use Tests\Support\Pending\PendingAssetManagerFunctionOverrides;

    function tempnam(string $directory, string $prefix): false|string
    {
        if (PendingAssetManagerFunctionOverrides::$failNextTempnam) {
            PendingAssetManagerFunctionOverrides::$failNextTempnam = false;

            return false;
        }

        $path                                                    = \tempnam($directory, $prefix);
        PendingAssetManagerFunctionOverrides::$lastTemporaryPath = $path === false ? null : $path;

        return $path;
    }

    function copy(string $from, string $to): bool
    {
        if (PendingAssetManagerFunctionOverrides::$failNextCopy) {
            PendingAssetManagerFunctionOverrides::$failNextCopy = false;

            return false;
        }

        return \copy($from, $to);
    }
}
