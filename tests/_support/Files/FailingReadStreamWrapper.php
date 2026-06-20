<?php

declare(strict_types=1);

namespace Tests\Support\Files;

final class FailingReadStreamWrapper
{
    public const string SCHEME = 'asset-connect-failing-read';

    public function stream_open(string $path, string $mode, int $options, ?string &$openedPath): bool
    {
        unset($path, $mode, $options, $openedPath);

        return true;
    }

    public function stream_read(int $count): false
    {
        unset($count);

        return false;
    }

    public function stream_eof(): bool
    {
        return false;
    }

    public static function path(string $fileName): string
    {
        return self::SCHEME . '://' . $fileName;
    }
}
