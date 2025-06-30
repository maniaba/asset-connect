<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Asset;

use JsonSerializable;
use Maniaba\FileConnect\Asset\Properties\AssetVariantProperty;
use Maniaba\FileConnect\Asset\Properties\BasicInfoProperty;
use Maniaba\FileConnect\Asset\Properties\UserCustomProperty;
use Stringable;

final class AssetMetadata implements JsonSerializable, Stringable
{
    public readonly UserCustomProperty $userCustom;
    public readonly AssetVariantProperty $fileVariant;
    public readonly BasicInfoProperty $basicInfo;

    public function __construct(array $metadata = [])
    {
        $this->userCustom  = UserCustomProperty::create($metadata);
        $this->fileVariant = AssetVariantProperty::create($metadata);
        $this->basicInfo   = BasicInfoProperty::create($metadata);
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
