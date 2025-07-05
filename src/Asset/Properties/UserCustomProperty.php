<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Asset\Properties;

use Override;

final class UserCustomProperty extends BaseProperty
{
    #[Override]
    public static function getName(): string
    {
        return 'user_custom';
    }
}
