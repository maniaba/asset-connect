<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Asset;

use JsonSerializable;
use Maniaba\FileConnect\Asset\Properties\AssetVariantProperties;
use Maniaba\FileConnect\Asset\Properties\BasicInfoProperties;
use Maniaba\FileConnect\Asset\Properties\UserCustomProperties;
use Stringable;

final class Properties implements JsonSerializable, Stringable
{
    public readonly UserCustomProperties $userCustom;
    public readonly AssetVariantProperties $fileVariant;
    public readonly BasicInfoProperties $basicInfo;

    public function __construct(array $properties = [])
    {
        $this->userCustom  = UserCustomProperties::create($properties);
        $this->fileVariant = AssetVariantProperties::create($properties);
        $this->basicInfo   = BasicInfoProperties::create($properties);
    }

    public function jsonSerialize(): array
    {
        return [
            ...$this->userCustom->jsonSerialize(),
            ...$this->fileVariant->jsonSerialize(),
            ...$this->basicInfo->jsonSerialize(),
        ];
    }

    public function __toString(): string
    {
        return json_encode($this->jsonSerialize(), JSON_THROW_ON_ERROR);
    }
}
