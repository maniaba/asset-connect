<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\UrlGenerator;

use CodeIgniter\I18n\Time;
use Maniaba\FileConnect\Asset\Asset;

class TempUrlToken
{
    private const CACHE_KEY_PREFIX = 'temporary_url_ac_';

    public static function createToken(Asset $asset, ?string $variant, Time $expiration): string
    {
        // Generate a unique token based on asset ID, variant, and expiration time
        $tokenData = [
            'asset_id'   => $asset->id,
            'variant'    => $variant,
            'expiration' => $expiration->getTimestamp(),
        ];

        // Create a hash of the token data
        $token = hash('sha256', json_encode($tokenData));

        // Store the token in cache with an expiration time
        service('cache')->save(self::CACHE_KEY_PREFIX . $token, $tokenData, $expiration->getTimestamp() - time());

        return $token;
    }

    public static function validateToken(string $token): ?array
    {
        // Retrieve the token data from cache
        $tokenData = service('cache')->get(self::CACHE_KEY_PREFIX . $token);

        if ($tokenData === null) {
            return null; // Token not found or expired
        }

        // Check if the token is still valid
        if (time() > $tokenData['expiration']) {
            service('cache')->delete(self::CACHE_KEY_PREFIX . $token); // Clean up expired token

            return null;
        }

        return $tokenData; // Return the valid token data
    }
}
