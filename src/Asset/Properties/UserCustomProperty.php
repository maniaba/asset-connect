<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Asset\Properties;

final class UserCustomProperty extends BaseProperty
{
    public static function getName(): string
    {
        return 'user_custom';
    }
}
