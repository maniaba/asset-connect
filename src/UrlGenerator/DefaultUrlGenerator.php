<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\UrlGenerator;

use CodeIgniter\Entity\Entity;
use CodeIgniter\I18n\Time;
use Maniaba\FileConnect\Asset\Asset;
use Maniaba\FileConnect\UrlGenerator\Interfaces\UrlGeneratorInterface;

final class DefaultUrlGenerator implements UrlGeneratorInterface
{
    private Asset $asset;
    private Entity $entity;

    public function __construct(Asset $asset, ?Entity $entity = null)
    {
        $this->asset  = $asset;
        $this->entity = $entity;
    }

    public function getUrl(?string $variantName = null): string
    {
    }

    public function getTemporaryUrl(Time $expiration, ?string $variantName = null, array $options = []): string
    {
        // needs to be implemented
    }
}
