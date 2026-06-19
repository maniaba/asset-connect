<?php

declare(strict_types=1);

namespace Tests\Pending;

use CodeIgniter\Config\Factories;
use CodeIgniter\Config\Services;
use CodeIgniter\Session\Session;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;
use Maniaba\AssetConnect\Config\Asset as AssetConfig;
use Maniaba\AssetConnect\Pending\PendingAsset;
use Maniaba\AssetConnect\Pending\PendingSecurityToken\SessionPendingSecurityToken;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @internal
 */
final class SessionPendingSecurityTokenTest extends CIUnitTestCase
{
    private const string SECURITY_KEY = 'asset-connect-hmac-test-key';

    /**
     * @var MockObject&Session
     */
    private MockObject $mockSession;

    /**
     * @var list<string>
     */
    private array $tempFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        $assetConfig                     = new AssetConfig();
        $assetConfig->pendingSecurityKey = self::SECURITY_KEY;
        Factories::injectMock('config', 'Asset', $assetConfig);

        $this->mockSession = $this->createMock(Session::class);
        Services::injectMock('session', $this->mockSession);
    }

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

    #[AllowMockObjectsWithoutExpectations]
    public function testConstructorThrowsExceptionWhenTTLIsZero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Token TTL must be a positive integer.');

        new SessionPendingSecurityToken(0);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testConstructorThrowsExceptionWhenTokenLengthIsInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Token length must be between 1 and 64 bytes.');

        new SessionPendingSecurityToken(3600, 65);
    }

    public function testGenerateTokenStoresPerPendingSessionSecretAndReturnsHmacDigest(): void
    {
        $pendingId = 'pending-id-1';
        $ttl       = 3600;

        $capturedSecret = null;

        $this->mockSession->expects($this->once())
            ->method('getTempdata')
            ->with($this->sessionKey($pendingId))
            ->willReturn(null);

        $this->mockSession->expects($this->once())
            ->method('setTempdata')
            ->with(
                $this->sessionKey($pendingId),
                $this->isString(),
                $ttl,
            )->willReturnCallback(static function (string $key, string $secret, int $ttlValue) use (&$capturedSecret): void {
                $capturedSecret = $secret;
            });

        $tokenService = new SessionPendingSecurityToken($ttl);
        $digest       = $tokenService->generateToken($pendingId);

        $this->assertIsString($capturedSecret);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $digest);
        $this->assertSame($this->expectedDigest($pendingId, $capturedSecret), $digest);
        $this->assertNotSame($capturedSecret, $digest);
    }

    public function testGenerateTokenReusesExistingPerPendingSessionSecret(): void
    {
        $pendingId      = 'pending-id-1';
        $existingSecret = 'existing-session-secret';

        $this->mockSession->expects($this->once())
            ->method('getTempdata')
            ->with($this->sessionKey($pendingId))
            ->willReturn($existingSecret);

        $this->mockSession->expects($this->never())->method('setTempdata');

        $tokenService = new SessionPendingSecurityToken();

        $this->assertSame(
            $this->expectedDigest($pendingId, $existingSecret),
            $tokenService->generateToken($pendingId),
        );
    }

    public function testRetrieveTokenReadsPerPendingSessionKey(): void
    {
        $pendingId = 'pending-id-1';

        $this->mockSession->expects($this->once())
            ->method('getTempdata')
            ->with($this->sessionKey($pendingId))
            ->willReturn('session-secret');

        $tokenService = new SessionPendingSecurityToken();

        $this->assertSame('session-secret', $tokenService->retrieveToken($pendingId));
    }

    public function testValidateTokenReturnsTrueForSameSessionSecret(): void
    {
        $pendingId     = 'pending-id-1';
        $sessionSecret = 'session-secret';
        $pendingAsset  = $this->createPendingAsset(
            $pendingId,
            $this->expectedDigest($pendingId, $sessionSecret),
        );

        $this->mockSession->expects($this->once())
            ->method('getTempdata')
            ->with($this->sessionKey($pendingId))
            ->willReturn($sessionSecret);

        $tokenService = new SessionPendingSecurityToken();

        $this->assertTrue($tokenService->validateToken($pendingAsset));
    }

    public function testValidateTokenReturnsFalseForDifferentSessionSecret(): void
    {
        $pendingId    = 'pending-id-1';
        $pendingAsset = $this->createPendingAsset(
            $pendingId,
            $this->expectedDigest($pendingId, 'uploader-session-secret'),
        );

        $this->mockSession->expects($this->once())
            ->method('getTempdata')
            ->with($this->sessionKey($pendingId))
            ->willReturn('other-session-secret');

        $tokenService = new SessionPendingSecurityToken();

        $this->assertFalse($tokenService->validateToken($pendingAsset));
    }

    public function testExplicitProvidedTokenCannotBypassSessionOwnership(): void
    {
        $pendingId    = 'pending-id-1';
        $pendingAsset = $this->createPendingAsset(
            $pendingId,
            $this->expectedDigest($pendingId, 'uploader-session-secret'),
        );

        $this->mockSession->expects($this->once())
            ->method('getTempdata')
            ->with($this->sessionKey($pendingId))
            ->willReturn('other-session-secret');

        $tokenService = new SessionPendingSecurityToken();

        $this->assertFalse($tokenService->validateToken(
            $pendingAsset,
            $pendingAsset->security_token,
        ));
    }

    public function testValidateTokenReturnsFalseWhenSessionSecretIsMissing(): void
    {
        $pendingId    = 'pending-id-1';
        $pendingAsset = $this->createPendingAsset(
            $pendingId,
            $this->expectedDigest($pendingId, 'uploader-session-secret'),
        );

        $this->mockSession->expects($this->once())
            ->method('getTempdata')
            ->with($this->sessionKey($pendingId))
            ->willReturn(null);

        $tokenService = new SessionPendingSecurityToken();

        $this->assertFalse($tokenService->validateToken($pendingAsset));
    }

    public function testDeleteTokenRemovesOnlyThePendingSessionKey(): void
    {
        $pendingId = 'pending-id-1';

        $this->mockSession->expects($this->once())
            ->method('removeTempdata')
            ->with($this->sessionKey($pendingId));

        $tokenService = new SessionPendingSecurityToken();
        $tokenService->deleteToken($pendingId);
    }

    private function createPendingAsset(string $pendingId, ?string $securityToken = null): PendingAsset
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_token_');
        $this->assertIsString($tempFile);
        $this->tempFiles[] = $tempFile;

        file_put_contents($tempFile, 'test content');

        $pendingAsset = PendingAsset::createFromFile($tempFile);
        $pendingAsset->setId($pendingId);

        if ($securityToken !== null) {
            $pendingAsset->setSecurityToken($securityToken);
        }

        return $pendingAsset;
    }

    private function sessionKey(string $pendingId): string
    {
        return SessionPendingSecurityToken::SESSION_KEY . '_' . hash('sha256', $pendingId);
    }

    private function expectedDigest(string $pendingId, string $sessionSecret): string
    {
        return hash_hmac(
            'sha256',
            "asset-connect.pending-token.v1\n" . $pendingId . "\n" . $sessionSecret,
            self::SECURITY_KEY,
        );
    }
}
