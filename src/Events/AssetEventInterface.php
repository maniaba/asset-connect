<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Events;

use Maniaba\FileConnect\Asset\Asset;

interface AssetEventInterface
{
    public function getAsset(): Asset;

    public static function name(): string;
}
