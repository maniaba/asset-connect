<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Asset\Properties;

use Override;

final class UserCustomProperty extends BaseProperty
{
    #[Override]
    public static function getName(): string
    {
        return 'user_custom';
    }
}
