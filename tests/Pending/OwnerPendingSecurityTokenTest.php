<?php

declare(strict_types=1);

namespace Tests\Pending;

use CodeIgniter\Config\Factories;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;
use Maniaba\AssetConnect\Config\Asset as AssetConfig;
use Maniaba\AssetConnect\Pending\Interfaces\PendingOwnerResolverInterface;
use Maniaba\AssetConnect\Pending\PendingAsset;
use Maniaba\AssetConnect\Pending\PendingSecurityToken\OwnerPendingSecurityToken;

/**
 * @internal
 */
final class OwnerPendingSecurityTokenTest extends CIUnitTestCase
{
    private const string SECURITY_KEY = 'asset-connect-owner-hmac-test-key';

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

    public function testGenerateTokenUsesCurrentOwnerFromResolver(): void
    {
        $pendingId = 'pending-id-1';
        $ownerId   = 'user-123';

        $this->injectAssetConfig($this->resolver($ownerId));

        $tokenService = new OwnerPendingSecurityToken();

        $this->assertSame(
            $this->expectedDigest($pendingId, $ownerId),
            $tokenService->generateToken($pendingId),
        );
    }

    public function testValidateTokenReturnsTrueForSameOwner(): void
    {
        $pendingId = 'pending-id-1';
        $ownerId   = 'user-123';

        $this->injectAssetConfig($this->resolver($ownerId));

        $pendingAsset = $this->createPendingAsset(
            $pendingId,
            $this->expectedDigest($pendingId, $ownerId),
        );

        $tokenService = new OwnerPendingSecurityToken();

        $this->assertTrue($tokenService->validateToken($pendingAsset));
    }

    public function testValidateTokenReturnsFalseForDifferentOwner(): void
    {
        $pendingId = 'pending-id-1';

        $this->injectAssetConfig($this->resolver('user-456'));

        $pendingAsset = $this->createPendingAsset(
            $pendingId,
            $this->expectedDigest($pendingId, 'user-123'),
        );

        $tokenService = new OwnerPendingSecurityToken();

        $this->assertFalse($tokenService->validateToken($pendingAsset));
    }

    public function testExplicitProvidedTokenCannotBypassOwnerResolver(): void
    {
        $pendingId = 'pending-id-1';

        $this->injectAssetConfig($this->resolver('user-456'));

        $pendingAsset = $this->createPendingAsset(
            $pendingId,
            $this->expectedDigest($pendingId, 'user-123'),
        );

        $tokenService = new OwnerPendingSecurityToken();

        $this->assertFalse($tokenService->validateToken(
            $pendingAsset,
            $pendingAsset->security_token,
        ));
    }

    public function testConstructorRequiresOwnerResolver(): void
    {
        $this->injectAssetConfig(null);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Pending owner resolver must be configured');

        new OwnerPendingSecurityToken();
    }

    public function testGenerateTokenRequiresResolvedOwner(): void
    {
        $this->injectAssetConfig($this->resolver(null));

        $tokenService = new OwnerPendingSecurityToken();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Pending owner resolver did not return an owner ID.');

        $tokenService->generateToken('pending-id-1');
    }

    private function injectAssetConfig(?PendingOwnerResolverInterface $resolver): void
    {
        $assetConfig                       = new AssetConfig();
        $assetConfig->pendingSecurityKey   = self::SECURITY_KEY;
        $assetConfig->pendingOwnerResolver = $resolver;

        Factories::injectMock('config', 'Asset', $assetConfig);
    }

    private function resolver(?string $ownerId): PendingOwnerResolverInterface
    {
        return new readonly class ($ownerId) implements PendingOwnerResolverInterface {
            public function __construct(private ?string $ownerId)
            {
            }

            public function resolveOwnerId(): ?string
            {
                return $this->ownerId;
            }
        };
    }

    private function createPendingAsset(string $pendingId, string $securityToken): PendingAsset
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_owner_token_');
        $this->assertIsString($tempFile);
        $this->tempFiles[] = $tempFile;

        file_put_contents($tempFile, 'test content');

        $pendingAsset = PendingAsset::createFromFile($tempFile);
        $pendingAsset->setId($pendingId);
        $pendingAsset->setSecurityToken($securityToken);

        return $pendingAsset;
    }

    private function expectedDigest(string $pendingId, string $ownerId): string
    {
        return hash_hmac(
            'sha256',
            "asset-connect.pending-token.v1\n" . $pendingId . "\n" . $ownerId,
            self::SECURITY_KEY,
        );
    }
}
