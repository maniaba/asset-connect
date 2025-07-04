<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Utils;

final class Format
{
    /**
     * Format bytes to a human-readable format
     *
     * @param int $bytes     The size in bytes
     * @param int $precision The number of decimal places
     *
     * @return string The formatted size
     */
    public static function formatBytesHumanReadable(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

        $bytes = max($bytes, 0);
        $pow   = floor(($bytes > 0 ? log($bytes) : 0) / log(1024));
        $pow   = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
