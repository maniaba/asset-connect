<?php

declare(strict_types=1);

namespace Tests\Support\Pending {
    final class PendingAssetManagerFunctionOverrides
    {
        public static bool $failNextCopy           = false;
        public static bool $failNextFopen          = false;
        public static bool $failNextTempnam        = false;
        public static ?string $lastTemporaryPath   = null;
        public static bool $sessionUnavailable     = false;
        public static bool $deleteStreamCopyTarget = false;

        /**
         * @var list<string>
         */
        public static array $randomBytesQueue = [];

        public static function reset(): void
        {
            self::$failNextCopy           = false;
            self::$failNextFopen          = false;
            self::$failNextTempnam        = false;
            self::$lastTemporaryPath      = null;
            self::$sessionUnavailable     = false;
            self::$deleteStreamCopyTarget = false;
            self::$randomBytesQueue       = [];
        }
    }
}

namespace Maniaba\AssetConnect\Pending {
    use Tests\Support\Pending\PendingAssetManagerFunctionOverrides;

    function random_bytes(int $length): string
    {
        if (PendingAssetManagerFunctionOverrides::$randomBytesQueue !== []) {
            return array_shift(PendingAssetManagerFunctionOverrides::$randomBytesQueue);
        }

        return \random_bytes($length);
    }

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

    /**
     * @param resource|null $context
     *
     * @return false|resource
     */
    function fopen(string $filename, string $mode, bool $useIncludePath = false, mixed $context = null)
    {
        if (PendingAssetManagerFunctionOverrides::$failNextFopen) {
            PendingAssetManagerFunctionOverrides::$failNextFopen = false;

            return false;
        }

        if (is_resource($context)) {
            return \fopen($filename, $mode, $useIncludePath, $context);
        }

        return \fopen($filename, $mode, $useIncludePath);
    }

    /**
     * @param resource $from
     * @param resource $to
     */
    function stream_copy_to_stream($from, $to, ?int $length = null, int $offset = 0): false|int
    {
        $result = $length === null
            ? \stream_copy_to_stream($from, $to)
            : \stream_copy_to_stream($from, $to, $length, $offset);

        if (PendingAssetManagerFunctionOverrides::$deleteStreamCopyTarget && is_resource($to)) {
            PendingAssetManagerFunctionOverrides::$deleteStreamCopyTarget = false;

            $uri = stream_get_meta_data($to)['uri'] ?? null;
            if (is_string($uri)) {
                @unlink($uri);
            }
        }

        return $result;
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

namespace Maniaba\AssetConnect\Pending\PendingSecurityToken {
    use Tests\Support\Pending\PendingAssetManagerFunctionOverrides;

    function service(string $name, ...$params): ?object
    {
        if ($name === 'session' && PendingAssetManagerFunctionOverrides::$sessionUnavailable) {
            return null;
        }

        return \service($name, ...$params);
    }
}
