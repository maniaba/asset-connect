<?php

declare(strict_types=1);

namespace Tests\Support\Files;

final class UnreadableStreamWrapper
{
    public const string SCHEME = 'asset-connect-unreadable';

    public function stream_open(string $path, string $mode, int $options, ?string &$openedPath): bool
    {
        unset($path, $mode, $options, $openedPath);

        return false;
    }

    /**
     * @return array<string, int>
     */
    public function url_stat(string $path, int $flags): array
    {
        unset($path, $flags);

        return [
            'dev'     => 0,
            'ino'     => 0,
            'mode'    => 0100000,
            'nlink'   => 1,
            'uid'     => 0,
            'gid'     => 0,
            'rdev'    => 0,
            'size'    => 1,
            'atime'   => time(),
            'mtime'   => time(),
            'ctime'   => time(),
            'blksize' => 0,
            'blocks'  => 0,
        ];
    }

    public static function path(string $fileName): string
    {
        return self::SCHEME . '://' . $fileName;
    }
}
