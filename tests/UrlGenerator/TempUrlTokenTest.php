<?php

declare(strict_types=1);

namespace Tests\UrlGenerator;

use CodeIgniter\Cache\CacheInterface;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;
use Maniaba\FileConnect\Asset\Asset;
use Maniaba\FileConnect\UrlGenerator\TempUrlToken;
use Override;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @internal
 */
final class TempUrlTokenTest extends CIUnitTestCase
{
    private CacheInterface|MockObject $mockCache;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        // Mock the cache service
        $this->mockCache = $this->createMock(CacheInterface::class);

        // Replace the cache service
        Services::injectMock('cache', $this->mockCache);
    }

    /**
     * Test createToken method
     */
    public function testCreateToken(): void
    {
        // Arrange
        $variant           = 'thumbnail';
        $expiration        = Time::now()->addHours(1);
        $expectedTokenData = [
            'asset_id'   => 123,
            'variant'    => $variant,
            'expiration' => $expiration->getTimestamp(),
        ];
        $expectedToken = hash('sha256', json_encode($expectedTokenData));

        // Setup expectations for the cache save method
        $this->mockCache->expects($this->once())
            ->method('save')
            ->with(
                'temporary_url_ac_' . $expectedToken,
                $expectedTokenData,
                $expiration->getTimestamp() - time(),
            )
            ->willReturn(true);

        // Act
        $token = TempUrlToken::createToken(new Asset([
            'id'       => 123,
            'metadata' => json_encode([
                'basic_info' => [
                    'file_relative_path' => 'uploads',
                    'collection_class'   => null, // Not a protected collection
                ],
            ]),
        ]), $variant, $expiration);

        // Assert
        $this->assertSame($expectedToken, $token);
    }

    /**
     * Test validateToken method with valid token
     */
    public function testValidateTokenWithValidToken(): void
    {
        // Arrange
        $token     = 'valid_token';
        $tokenData = [
            'asset_id'   => '123',
            'variant'    => 'thumbnail',
            'expiration' => Time::now()->addYears(1)->getTimestamp(), // (future)
        ];

        // Setup expectations for the cache get method
        $this->mockCache->expects($this->once())
            ->method('get')
            ->with('temporary_url_ac_' . $token)
            ->willReturn($tokenData);

        // Act
        $result = TempUrlToken::validateToken($token);

        // Assert
        $this->assertSame($tokenData, $result);
    }

    /**
     * Test validateToken method with expired token
     */
    public function testValidateTokenWithExpiredToken(): void
    {
        // Arrange
        $token     = 'expired_token';
        $tokenData = [
            'asset_id'   => '123',
            'variant'    => 'thumbnail',
            'expiration' => 1625011200, // 2021-06-30 00:00:00 UTC (past)
        ];

        // Setup expectations for the cache get method
        $this->mockCache->expects($this->once())
            ->method('get')
            ->with('temporary_url_ac_' . $token)
            ->willReturn($tokenData);

        // Setup expectations for the cache delete method
        $this->mockCache->expects($this->once())
            ->method('delete')
            ->with('temporary_url_ac_' . $token);

        // Act
        $result = TempUrlToken::validateToken($token);

        // Assert
        $this->assertNull($result);
    }

    /**
     * Test validateToken method with non-existent token
     */
    public function testValidateTokenWithNonExistentToken(): void
    {
        // Arrange
        $token = 'non_existent_token';

        // Setup expectations for the cache get method
        $this->mockCache->expects($this->once())
            ->method('get')
            ->with('temporary_url_ac_' . $token)
            ->willReturn(null);

        // Act
        $result = TempUrlToken::validateToken($token);

        // Assert
        $this->assertNull($result);
    }
}
