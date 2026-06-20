<?php

declare(strict_types=1);

namespace Tests\Pending;

use CodeIgniter\Test\CIUnitTestCase;
use Maniaba\AssetConnect\Pending\PendingAsset;
use Tests\Support\Pending\PlainPendingSecurityToken;

/**
 * @internal
 */
final class AbstractPendingSecurityTokenTest extends CIUnitTestCase
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

        parent::tearDown();
    }

    public function testConstructorInitializesTokenAndRandomTokenUsesConfiguredByteLength(): void
    {
        $tokenService = new PlainPendingSecurityToken(3600, 4);

        $this->assertTrue($tokenService->initialized, 'Token service must call initialize during construction.');
        $this->assertMatchesRegularExpression(
            '/^[a-f0-9]{8}$/',
            $tokenService->generateToken('pending-id'),
            'Random token must be hex encoded with two characters per configured byte.',
        );
    }

    public function testValidateTokenUsesProvidedToken(): void
    {
        $pendingAsset = $this->createPendingAsset('pending-id', 'plain-token');
        $tokenService = new PlainPendingSecurityToken();

        $this->assertTrue(
            $tokenService->validateToken($pendingAsset, 'plain-token'),
            'Provided token matching pending metadata must validate.',
        );
        $this->assertFalse(
            $tokenService->validateToken($pendingAsset, 'different-token'),
            'Provided token different from pending metadata must fail validation.',
        );
    }

    public function testValidateTokenRetrievesTokenWhenNoTokenIsProvided(): void
    {
        $pendingAsset              = $this->createPendingAsset('pending-id', 'stored-token');
        $tokenService              = new PlainPendingSecurityToken();
        $tokenService->storedToken = 'stored-token';

        $this->assertTrue(
            $tokenService->validateToken($pendingAsset),
            'Token service must use retrieved token when no explicit token is provided.',
        );
    }

    public function testValidateTokenFailsWithoutProvidedOrStoredToken(): void
    {
        $pendingAsset = $this->createPendingAsset('pending-id', 'stored-token');
        $tokenService = new PlainPendingSecurityToken();

        $this->assertFalse(
            $tokenService->validateToken($pendingAsset),
            'Validation must fail when neither explicit nor stored token is available.',
        );
    }

    public function testValidateTokenFailsWhenPendingAssetHasNoSecurityToken(): void
    {
        $pendingAsset              = $this->createPendingAsset('pending-id');
        $tokenService              = new PlainPendingSecurityToken();
        $tokenService->storedToken = 'stored-token';

        $this->assertFalse(
            $tokenService->validateToken($pendingAsset),
            'Validation must fail when pending asset metadata has no security token.',
        );
    }

    private function createPendingAsset(string $pendingId, ?string $securityToken = null): PendingAsset
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_plain_token_');
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
}
