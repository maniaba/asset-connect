<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\PathGenerator;

use CodeIgniter\I18n\Time;

final class PathGeneratorHelper
{
    public function getUniqueId(bool $moreEntropy = false): string
    {
        if (! $moreEntropy) {
            return uniqid(time() . '_');
        }

        // Generate a unique ID using a combination of time and random bytes
        $uniqueId = bin2hex(random_bytes(16)) . time();

        // Ensure the ID is unique by hashing it
        return hash('sha256', $uniqueId);
    }

    /**
     * Generates a date-time string formatted as a folder name.
     *
     * @return string The formatted date-time string.
     */
    public function getDateTime(): string
    {
        return $this->getPathString($this->getDateTimeFormat('Y-m-d'), $this->getDateTimeFormat('His.U'));
    }

    public function getTime(): string
    {
        return $this->getDateTimeFormat('His.U');
    }

    private function getDateTimeFormat(string $format): string
    {
        return Time::now()->format($format);
    }

    /**
     * Generates a path string by joining the given segments with the system's directory separator.
     *
     * @param string ...$segments The segments to be joined into a path.
     *
     * @return string The joined path string.
     */
    public function getPathString(string ...$segments): string
    {
        return implode(DIRECTORY_SEPARATOR, $segments);
    }
}
