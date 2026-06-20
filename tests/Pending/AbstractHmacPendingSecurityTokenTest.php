<?php

declare(strict_types=1);

namespace Tests\Pending;

use CodeIgniter\Config\Factories;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Encryption;
use InvalidArgumentException;
use Maniaba\AssetConnect\Config\Asset as AssetConfig;
use Maniaba\AssetConnect\Pending\PendingAsset;
use Tests\Support\Pending\TestHmacPendingSecurityToken;

/**
 * @internal
 */
final class AbstractHmacPendingSecurityTokenTest extends CIUnitTestCase
{
    /**
     * @var list<string>
     */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $tempFile) {
            if (is_file($tempFile)) {
                unlink($tempFile);
            }
        }

        Factories::reset('config');

        parent::tearDown();
    }

    public function testValidateTokenUsesEncryptionKeyFallbackAndCachesResolvedKey(): void
    {
        $pendingId  = 'pending-hmac-id';
        $ownerProof = 'owner-proof';

        $this->injectAssetConfig(null, 'fallback-encryption-key');

        $pendingAsset = $this->createPendingAsset(
            $pendingId,
            $this->expectedDigest($pendingId, $ownerProof, 'fallback-encryption-key'),
        );

        $tokenService             = new TestHmacPendingSecurityToken();
        $tokenService->ownerProof = $ownerProof;

        $this->assertTrue(
            $tokenService->validateToken($pendingAsset, 'ignored-explicit-token'),
            'HMAC token validation must use owner proof from retrieveToken, not the explicit submitted token.',
        );

        $this->injectAssetConfig('changed-security-key', 'changed-encryption-key');

        $this->assertTrue(
            $tokenService->validateToken($pendingAsset),
            'Resolved HMAC security key must be cached for the token service instance.',
        );
    }

    public function testValidateTokenThrowsWhenNoSecurityKeyIsConfigured(): void
    {
        $this->injectAssetConfig(null, '');

        $pendingAsset             = $this->createPendingAsset('pending-hmac-id', 'irrelevant-token');
        $tokenService             = new TestHmacPendingSecurityToken();
        $tokenService->ownerProof = 'owner-proof';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Pending security key must be configured via Config\Asset::$pendingSecurityKey or Config\Encryption::$key.',
        );

        $tokenService->validateToken($pendingAsset);
    }

    public function testValidateTokenReturnsFalseWithoutOwnerProof(): void
    {
        $this->injectAssetConfig('asset-security-key', null);

        $pendingAsset = $this->createPendingAsset('pending-hmac-id', 'irrelevant-token');
        $tokenService = new TestHmacPendingSecurityToken();

        $this->assertFalse(
            $tokenService->validateToken($pendingAsset),
            'HMAC token validation must fail when no owner proof can be retrieved.',
        );

        $tokenService->ownerProof = '';

        $this->assertFalse(
            $tokenService->validateToken($pendingAsset),
            'HMAC token validation must fail when retrieved owner proof is an empty string.',
        );
    }

    public function testValidateTokenReturnsFalseWhenPendingSecurityTokenIsMissing(): void
    {
        $this->injectAssetConfig('asset-security-key', null);

        $pendingAsset             = $this->createPendingAsset('pending-hmac-id');
        $tokenService             = new TestHmacPendingSecurityToken();
        $tokenService->ownerProof = 'owner-proof';

        $this->assertFalse(
            $tokenService->validateToken($pendingAsset),
            'HMAC token validation must fail when pending asset metadata has no security token.',
        );
    }

    private function injectAssetConfig(?string $pendingSecurityKey, ?string $encryptionKey): void
    {
        $assetConfig                     = new AssetConfig();
        $assetConfig->pendingSecurityKey = $pendingSecurityKey;

        $encryptionConfig      = new Encryption();
        $encryptionConfig->key = $encryptionKey ?? '';

        Factories::injectMock('config', 'Asset', $assetConfig);
        Factories::injectMock('config', 'Encryption', $encryptionConfig);
    }

    private function createPendingAsset(string $pendingId, ?string $securityToken = null): PendingAsset
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_hmac_token_');
        $this->assertIsString($tempFile, 'Test temp file must be created.');
        $this->tempFiles[] = $tempFile;

        file_put_contents($tempFile, 'test content');

        $pendingAsset = PendingAsset::createFromFile($tempFile);
        $pendingAsset->setId($pendingId);

        if ($securityToken !== null) {
            $pendingAsset->setSecurityToken($securityToken);
        }

        return $pendingAsset;
    }

    private function expectedDigest(string $pendingId, string $ownerProof, string $securityKey): string
    {
        return hash_hmac(
            'sha256',
            "asset-connect.pending-token.v1\n" . $pendingId . "\n" . $ownerProof,
            $securityKey,
        );
    }
}
