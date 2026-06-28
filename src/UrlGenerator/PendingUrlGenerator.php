<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\UrlGenerator;

use Maniaba\AssetConnect\Config\Asset as AssetConfig;
use Maniaba\AssetConnect\Exceptions\InvalidArgumentException;
use Maniaba\AssetConnect\Pending\PendingAsset;
use Maniaba\AssetConnect\UrlGenerator\Interfaces\PendingUrlGeneratorInterface;
use Maniaba\AssetConnect\UrlGenerator\Interfaces\UrlGeneratorInterface;

final readonly class PendingUrlGenerator
{
    private function __construct(private PendingAsset $pendingAsset)
    {
    }

    public function getUrl(bool $forceDownload = false): string
    {
        $path = self::routeTo('asset-connect.pending', $this->pendingAsset);

        if ($path === '') {
            return '';
        }

        $url = self::toAbsoluteUrl($path);

        return $forceDownload ? self::appendQuery($url, 'download=force') : $url;
    }

    public static function routeTo(string $routeName, PendingAsset $pendingAsset): string
    {
        if ($pendingAsset->id === '') {
            return '';
        }

        /** @var AssetConfig $config */
        $config       = config('Asset');
        $urlGenerator = $config->defaultUrlGenerator;

        if ($urlGenerator === null) {
            return '';
        }

        if (! is_subclass_of($urlGenerator, UrlGeneratorInterface::class)) {
            throw new InvalidArgumentException("The URL generator class '{$urlGenerator}' must implement the UrlGeneratorInterface.");
        }

        if (! is_subclass_of($urlGenerator, PendingUrlGeneratorInterface::class)) {
            throw new InvalidArgumentException("The URL generator class '{$urlGenerator}' must implement the PendingUrlGeneratorInterface to generate pending asset URLs.");
        }

        $params = $urlGenerator::pendingParams($pendingAsset);

        if (! isset($params[$routeName])) {
            throw new InvalidArgumentException("Route '{$routeName}' is not defined in the pending URL generator.");
        }

        $path = route_to($routeName, ...$params[$routeName]);

        if ($path === false) {
            throw new InvalidArgumentException("Could not generate URL for pending asset '{$pendingAsset->id}'. Please ensure the route '{$routeName}' is defined.");
        }

        return $path;
    }

    public static function create(PendingAsset $pendingAsset): self
    {
        return new self($pendingAsset);
    }

    public static function toRelativeUrl(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (! is_string($path) || $path === '') {
            return $url;
        }

        $query = parse_url($url, PHP_URL_QUERY);
        if (is_string($query) && $query !== '') {
            return $path . '?' . $query;
        }

        return $path;
    }

    private static function toAbsoluteUrl(string $url): string
    {
        if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $url) === 1 || str_starts_with($url, '//')) {
            return $url;
        }

        return site_url(ltrim($url, '/'));
    }

    private static function appendQuery(string $url, string $query): string
    {
        return $url . (str_contains($url, '?') ? '&' : '?') . $query;
    }
}
