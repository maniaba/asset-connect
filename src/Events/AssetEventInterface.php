<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Events;

use Maniaba\AssetConnect\Asset\Asset;

interface AssetEventInterface
{
    public function getAsset(): Asset;

    public static function name(): string;
}
