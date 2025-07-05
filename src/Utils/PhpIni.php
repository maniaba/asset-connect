<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Utils;

final class PhpIni
{
    /**
     * Converts PHP "shorthand" notation (e.g., 2M, 512K, 1G) to bytes.
     */
    private static function shorthandToBytes(string $value): int
    {
        $value = trim($value);
        $last  = strtolower($value[strlen($value) - 1]);   // Last character (m, k, g)
        $num   = (int) $value;                              // numerical part

        switch ($last) {
            case 'g':  // Gigabytes -> multiply three times by 1024
                $num *= 1024;

                // without break; falls through to 'm'
                // no break
            case 'm':  // megabytes → multiply two times by 1024
                $num *= 1024;

                // without break; falls through to 'k'
                // no break
            case 'k':  // kilobytes → multiply once by 1024
                $num *= 1024;
                break;
        }

        return $num;
    }

    /**
     * Gets the upload_max_filesize from the ini settings and returns it as bytes.
     */
    public static function uploadMaxFilesizeBytes(): int
    {
        return self::shorthandToBytes((string) ini_get('upload_max_filesize'));
    }
}
