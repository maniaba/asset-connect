<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Asset;

use JsonSerializable;
use Maniaba\AssetConnect\Asset\Properties\AssetVariantProperty;
use Maniaba\AssetConnect\Asset\Properties\UserCustomProperty;
use Override;
use Stringable;

final readonly class AssetMetadata implements JsonSerializable, Stringable
{
    public UserCustomProperty $userCustom;
    public AssetVariantProperty $assetVariant;

    public function __construct(array $metadata = [])
    {
        $this->userCustom   = UserCustomProperty::create($metadata);
        $this->assetVariant = AssetVariantProperty::create($metadata);
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return [
            ...$this->userCustom->jsonSerialize(),
            ...$this->assetVariant->jsonSerialize(),
        ];
    }

    #[Override]
    public function __toString(): string
    {
        return json_encode($this->jsonSerialize(), JSON_THROW_ON_ERROR);
    }
}
