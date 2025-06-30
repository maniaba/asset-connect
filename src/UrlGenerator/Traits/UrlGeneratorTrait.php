<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\UrlGenerator\Traits;

use CodeIgniter\Entity\Entity;
use CodeIgniter\I18n\Time;
use Maniaba\FileConnect\UrlGenerator\DefaultUrlGenerator;
use Maniaba\FileConnect\UrlGenerator\Interfaces\UrlGeneratorInterface;

trait UrlGeneratorTrait
{
    /**
     * Get a URL generator for this asset
     *
     * @param UrlGeneratorInterface|null $urlGenerator Custom URL generator to use, or null to use the default
     * @param Entity|null                $entity       The entity to check authorization for, or null to skip authorization
     *
     * @return UrlGeneratorInterface The URL generator for this asset
     */
    public function getUrlGenerator(?UrlGeneratorInterface $urlGenerator = null, ?Entity $entity = null): UrlGeneratorInterface
    {
        if ($urlGenerator !== null) {
            return $urlGenerator;
        }

        return new DefaultUrlGenerator($this, $entity);
    }

    /**
     * Get the URL for this asset, optionally specifying a variant
     *
     * @param string                     $variantName  The name of the variant to get the URL for, or empty for the original asset
     * @param UrlGeneratorInterface|null $urlGenerator Custom URL generator to use, or null to use the default
     * @param Entity|null                $entity       The entity to check authorization for, or null to skip authorization
     *
     * @return string The URL to the asset
     */
    public function getUrl(string $variantName = '', ?UrlGeneratorInterface $urlGenerator = null, ?Entity $entity = null): string
    {
        return $this->getUrlGenerator($urlGenerator, $entity)->getUrl($variantName);
    }

    /**
     * Get a temporary URL for this asset that expires after the specified time
     *
     * @param Time                       $expiration   The time when the URL should expire
     * @param string                     $variantName  The name of the variant to get the URL for, or empty for the original asset
     * @param array                      $options      Additional options for the URL generation
     * @param UrlGeneratorInterface|null $urlGenerator Custom URL generator to use, or null to use the default
     * @param Entity|null                $entity       The entity to check authorization for, or null to skip authorization
     *
     * @return string The temporary URL to the asset
     */
    public function getTemporaryUrl(Time $expiration, string $variantName = '', array $options = [], ?UrlGeneratorInterface $urlGenerator = null, ?Entity $entity = null): string
    {
        return $this->getUrlGenerator($urlGenerator, $entity)->getTemporaryUrl($expiration, $variantName, $options);
    }
}
