<?php

declare(strict_types=1);

namespace Tests\Support\Storage {
    final class TemporaryStorageFileFunctionOverrides
    {
        public static bool $failNextFopen   = false;
        public static bool $failNextTempnam = false;

        public static function reset(): void
        {
            self::$failNextFopen   = false;
            self::$failNextTempnam = false;
        }
    }
}

namespace Maniaba\AssetConnect\Storage {
    use Tests\Support\Storage\TemporaryStorageFileFunctionOverrides;

    function tempnam(string $directory, string $prefix): false|string
    {
        if (TemporaryStorageFileFunctionOverrides::$failNextTempnam) {
            TemporaryStorageFileFunctionOverrides::$failNextTempnam = false;

            return false;
        }

        return \tempnam($directory, $prefix);
    }

    /**
     * @param resource|null $context
     *
     * @return false|resource
     */
    function fopen(string $filename, string $mode, bool $useIncludePath = false, mixed $context = null)
    {
        if (TemporaryStorageFileFunctionOverrides::$failNextFopen) {
            TemporaryStorageFileFunctionOverrides::$failNextFopen = false;

            return false;
        }

        if (is_resource($context)) {
            return \fopen($filename, $mode, $useIncludePath, $context);
        }

        return \fopen($filename, $mode, $useIncludePath);
    }
}
