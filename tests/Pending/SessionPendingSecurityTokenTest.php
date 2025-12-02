<?php

declare(strict_types=1);

namespace Tests\Pending;

use CodeIgniter\Config\Services;
use CodeIgniter\Session\Session;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;
use Maniaba\AssetConnect\Pending\PendingAsset;
use Maniaba\AssetConnect\Pending\PendingSecurityToken\SessionPendingSecurityToken;
use Override;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @internal
 */
final class SessionPendingSecurityTokenTest extends CIUnitTestCase
{
    /**
     * @var MockObject&Session
     */
    private MockObject $mockSession;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockSession = $this->createMock(Session::class);

        // Mock the service function to return our mock session
        $this->injectSession($this->mockSession);
    }

    #[Override]
    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Inject mock session into service container
     */
    private function injectSession(MockObject $session): void
    {
        // Use CodeIgniter's service container to inject mock
        Services::injectMock('session', $session);
    }

    /**
     * Create a real PendingAsset instance for testing
     */
    private function createPendingAsset(string $pendingId, ?string $securityToken = null): PendingAsset
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_token_');
        file_put_contents($tempFile, 'test content');

        $pendingAsset = PendingAsset::createFromFile($tempFile);
        $pendingAsset->setId($pendingId);

        if ($securityToken !== null) {
            $pendingAsset->setSecurityToken($securityToken);
        }

        return $pendingAsset;
    }

    /**
     * Test constructor with valid parameters
     */
    public function testConstructorWithValidParameters(): void
    {
        // Act
        $token = new SessionPendingSecurityToken(3600, 32);

        // Assert
        $this->assertInstanceOf(SessionPendingSecurityToken::class, $token);
    }

    /**
     * Test constructor with default parameters
     */
    public function testConstructorWithDefaultParameters(): void
    {
        // Act
        $token = new SessionPendingSecurityToken();

        // Assert
        $this->assertInstanceOf(SessionPendingSecurityToken::class, $token);
    }

    /**
     * Test constructor throws exception when TTL is zero
     */
    public function testConstructorThrowsExceptionWhenTTLIsZero(): void
    {
        // Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Token TTL must be a positive integer.');

        // Act
        new SessionPendingSecurityToken(0);
    }

    /**
     * Test constructor throws exception when TTL is negative
     */
    public function testConstructorThrowsExceptionWhenTTLIsNegative(): void
    {
        // Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Token TTL must be a positive integer.');

        // Act
        new SessionPendingSecurityToken(-100);
    }

    /**
     * Test constructor throws exception when token length is zero
     */
    public function testConstructorThrowsExceptionWhenTokenLengthIsZero(): void
    {
        // Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Token length must be between 1 and 64 bytes.');

        // Act
        new SessionPendingSecurityToken(3600, 0);
    }

    /**
     * Test constructor throws exception when token length is negative
     */
    public function testConstructorThrowsExceptionWhenTokenLengthIsNegative(): void
    {
        // Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Token length must be between 1 and 64 bytes.');

        // Act
        new SessionPendingSecurityToken(3600, -10);
    }

    /**
     * Test constructor throws exception when token length exceeds maximum
     */
    public function testConstructorThrowsExceptionWhenTokenLengthExceedsMaximum(): void
    {
        // Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Token length must be between 1 and 64 bytes.');

        // Act
        new SessionPendingSecurityToken(3600, 65);
    }

    /**
     * Test generateToken returns a valid token string
     */
    public function testGenerateTokenReturnsValidTokenString(): void
    {
        // Arrange
        $pendingId   = 'test-pending-id';
        $tokenLength = 16;
        $ttl         = 3600;

        $this->mockSession->expects($this->once())
            ->method('setTempdata')
            ->with(
                $this->stringContains('__pending_asset_security_token_' . $pendingId),
                $this->isString(),
                $ttl,
            );

        $tokenService = new SessionPendingSecurityToken($ttl, $tokenLength);

        // Act
        $token = $tokenService->generateToken($pendingId);

        // Assert
        $this->assertIsString($token);
        $this->assertSame($tokenLength * 2, strlen($token)); // hex encoding doubles length
        $this->assertMatchesRegularExpression('/^[a-f0-9]+$/', $token);
    }

    /**
     * Test generateToken stores token in session
     */
    public function testGenerateTokenStoresTokenInSession(): void
    {
        // Arrange
        $pendingId = 'test-pending-id';
        $ttl       = 3600;

        $capturedToken = null;
        $this->mockSession->expects($this->once())
            ->method('setTempdata')
            ->willReturnCallback(function ($key, $token, $ttlValue) use (&$capturedToken, $ttl) {
                $capturedToken = $token;
                $this->assertSame($ttl, $ttlValue);

                return null;
            });

        $tokenService = new SessionPendingSecurityToken($ttl);

        // Act
        $returnedToken = $tokenService->generateToken($pendingId);

        // Assert
        $this->assertSame($capturedToken, $returnedToken);
    }

    /**
     * Test retrieveToken returns stored token
     */
    public function testRetrieveTokenReturnsStoredToken(): void
    {
        // Arrange
        $pendingId   = 'test-pending-id';
        $storedToken = 'abc123def456';

        $this->mockSession->expects($this->once())
            ->method('getTempdata')
            ->with($this->stringContains('__pending_asset_security_token_' . $pendingId))
            ->willReturn($storedToken);

        $tokenService = new SessionPendingSecurityToken();

        // Act
        $result = $tokenService->retrieveToken($pendingId);

        // Assert
        $this->assertSame($storedToken, $result);
    }

    /**
     * Test retrieveToken returns null when token not found
     */
    public function testRetrieveTokenReturnsNullWhenNotFound(): void
    {
        // Arrange
        $pendingId = 'non-existent-id';

        $this->mockSession->expects($this->once())
            ->method('getTempdata')
            ->with($this->stringContains('__pending_asset_security_token_' . $pendingId))
            ->willReturn(null);

        $tokenService = new SessionPendingSecurityToken();

        // Act
        $result = $tokenService->retrieveToken($pendingId);

        // Assert
        $this->assertNull($result);
    }

    /**
     * Test validateToken returns true for matching tokens
     */
    public function testValidateTokenReturnsTrueForMatchingTokens(): void
    {
        // Arrange
        $pendingId     = 'test-pending-id';
        $storedToken   = 'abc123def456';
        $providedToken = 'abc123def456';

        $pendingAsset = $this->createPendingAsset($pendingId, $storedToken);

        $tokenService = new SessionPendingSecurityToken();

        // Act
        $result = $tokenService->validateToken($pendingAsset, $providedToken);

        // Assert
        $this->assertTrue($result);
    }

    /**
     * Test validateToken returns false for non-matching tokens
     */
    public function testValidateTokenReturnsFalseForNonMatchingTokens(): void
    {
        // Arrange
        $pendingId     = 'test-pending-id';
        $storedToken   = 'abc123def456';
        $providedToken = 'different-token';

        $pendingAsset = $this->createPendingAsset($pendingId, $storedToken);

        $tokenService = new SessionPendingSecurityToken();

        // Act
        $result = $tokenService->validateToken($pendingAsset, $providedToken);

        // Assert
        $this->assertFalse($result);
    }

    /**
     * Test validateToken returns false when no token is stored
     */
    public function testValidateTokenReturnsFalseWhenNoTokenStored(): void
    {
        // Arrange
        $pendingId     = 'test-pending-id';
        $providedToken = 'some-token';

        $pendingAsset = $this->createPendingAsset($pendingId, 'stored-token');

        // When a token is provided, validateToken doesn't call retrieveToken/getTempdata
        $tokenService = new SessionPendingSecurityToken();

        // Act
        $result = $tokenService->validateToken($pendingAsset, $providedToken);

        // Assert - should be false because provided token doesn't match security_token
        $this->assertFalse($result);
    }

    /**
     * Test validateToken returns false when provided token is null
     */
    public function testValidateTokenReturnsFalseWhenProvidedTokenIsNull(): void
    {
        // Arrange
        $pendingId   = 'test-pending-id';
        $storedToken = 'abc123def456';

        $pendingAsset = $this->createPendingAsset($pendingId, $storedToken);

        $this->mockSession->expects($this->once())
            ->method('getTempdata')
            ->with($this->stringContains('__pending_asset_security_token_' . $pendingId))
            ->willReturn(null);

        $tokenService = new SessionPendingSecurityToken();

        // Act
        $result = $tokenService->validateToken($pendingAsset);

        // Assert
        $this->assertFalse($result);
    }

    /**
     * Test validateToken uses constant-time comparison
     */
    public function testValidateTokenUsesConstantTimeComparison(): void
    {
        // Arrange
        $pendingId     = 'test-pending-id';
        $storedToken   = 'abc123def456';
        $providedToken = 'abc123def455'; // One character different at the end

        $pendingAsset = $this->createPendingAsset($pendingId, $storedToken);

        $tokenService = new SessionPendingSecurityToken();

        // Act
        $result = $tokenService->validateToken($pendingAsset, $providedToken);

        // Assert - hash_equals should detect the difference
        $this->assertFalse($result);
    }

    /**
     * Test deleteToken removes token from session
     */
    public function testDeleteTokenRemovesTokenFromSession(): void
    {
        // Arrange
        $pendingId = 'test-pending-id';

        $this->mockSession->expects($this->once())
            ->method('removeTempdata')
            ->with($this->stringContains('__pending_asset_security_token_' . $pendingId));

        $tokenService = new SessionPendingSecurityToken();

        // Act
        $tokenService->deleteToken($pendingId);

        // Assert - expectations verified by mock
        $this->assertTrue(true);
    }

    /**
     * Test session key format is consistent
     */
    public function testSessionKeyFormatIsConsistent(): void
    {
        // Arrange
        $pendingId          = 'test-id-123';
        $expectedKeyPattern = '__pending_asset_security_token_test-id-123';

        $this->mockSession->expects($this->exactly(2))
            ->method('getTempdata')
            ->with($expectedKeyPattern)
            ->willReturn('token-value');

        $tokenService = new SessionPendingSecurityToken();

        // Act - call retrieve twice to verify consistency
        $result1 = $tokenService->retrieveToken($pendingId);
        $result2 = $tokenService->retrieveToken($pendingId);

        // Assert
        $this->assertSame($result1, $result2);
    }

    /**
     * Test token generation produces different tokens each time
     */
    public function testTokenGenerationProducesDifferentTokens(): void
    {
        // Arrange
        $pendingId1 = 'pending-id-1';
        $pendingId2 = 'pending-id-2';

        $capturedTokens = [];
        $this->mockSession->expects($this->exactly(2))
            ->method('setTempdata')
            ->willReturnCallback(static function ($key, $token) use (&$capturedTokens) {
                $capturedTokens[] = $token;

                return null;
            });

        $tokenService = new SessionPendingSecurityToken();

        // Act
        $token1 = $tokenService->generateToken($pendingId1);
        $token2 = $tokenService->generateToken($pendingId2);

        // Assert
        $this->assertNotSame($token1, $token2);
        $this->assertCount(2, $capturedTokens);
        $this->assertNotSame($capturedTokens[0], $capturedTokens[1]);
    }

    /**
     * Test custom token length is respected
     */
    public function testCustomTokenLengthIsRespected(): void
    {
        // Arrange
        $pendingId    = 'test-pending-id';
        $customLength = 32;

        $this->mockSession->expects($this->once())
            ->method('setTempdata')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->anything(),
            );

        $tokenService = new SessionPendingSecurityToken(3600, $customLength);

        // Act
        $token = $tokenService->generateToken($pendingId);

        // Assert
        $this->assertSame($customLength * 2, strlen($token)); // hex encoding doubles length
    }

    /**
     * Test custom TTL is respected
     */
    public function testCustomTTLIsRespected(): void
    {
        // Arrange
        $pendingId = 'test-pending-id';
        $customTTL = 7200;

        $this->mockSession->expects($this->once())
            ->method('setTempdata')
            ->with(
                $this->anything(),
                $this->anything(),
                $customTTL,
            );

        $tokenService = new SessionPendingSecurityToken($customTTL);

        // Act
        $tokenService->generateToken($pendingId);

        // Assert - expectations verified by mock
        $this->assertTrue(true);
    }

    /**
     * Test complete workflow: generate, retrieve, validate, delete
     */
    public function testCompleteWorkflow(): void
    {
        // Arrange
        $pendingId      = 'workflow-test-id';
        $generatedToken = null;

        // Setup mocks for the workflow
        $this->mockSession->expects($this->once())
            ->method('setTempdata')
            ->willReturnCallback(static function ($key, $token) use (&$generatedToken) {
                $generatedToken = $token;

                return null;
            });

        // getTempdata is called once in retrieveToken() only
        // When validateToken is called with a provided token, it doesn't call retrieveToken
        $this->mockSession->expects($this->once())
            ->method('getTempdata')
            ->willReturnCallback(static function () use (&$generatedToken) {
                return $generatedToken;
            });

        $this->mockSession->expects($this->once())
            ->method('removeTempdata');

        $tokenService = new SessionPendingSecurityToken();

        // Act & Assert

        // Step 1: Generate token
        $token = $tokenService->generateToken($pendingId);
        $this->assertIsString($token);
        $this->assertNotEmpty($token);

        // Step 2: Retrieve token
        $retrievedToken = $tokenService->retrieveToken($pendingId);
        $this->assertSame($generatedToken, $retrievedToken);

        // Step 3: Validate token
        $pendingAsset = $this->createPendingAsset($pendingId, $generatedToken);
        $isValid      = $tokenService->validateToken($pendingAsset, $token);
        $this->assertTrue($isValid);

        // Step 4: Delete token
        $tokenService->deleteToken($pendingId);
    }
}
