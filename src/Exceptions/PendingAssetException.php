<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Exceptions;

use CodeIgniter\Exceptions\LogicException;

final class PendingAssetException extends LogicException
{
    public static function forUnableToReadMetadata(string $id): self
    {
        return new self(lang('AssetConnect.exceptions.unable_to_read_metadata', [
            'id' => $id,
        ]));
    }

    public static function forUnableToStorePendingAsset(string $id, string $message): self
    {
        return new self(lang('AssetConnect.exceptions.unable_to_store_pending_asset', [
            'id'      => $id,
            'message' => $message,
        ]));
    }
}
