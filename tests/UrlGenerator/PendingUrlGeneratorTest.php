<?php

declare(strict_types=1);

namespace Tests\UrlGenerator;

use CodeIgniter\Config\Factories;
use CodeIgniter\Config\Services;
use CodeIgniter\Test\CIUnitTestCase;
use Maniaba\AssetConnect\Config\Asset as AssetConfig;
use Maniaba\AssetConnect\Exceptions\InvalidArgumentException;
use Maniaba\AssetConnect\Pending\PendingAsset;
use Maniaba\AssetConnect\UrlGenerator\PendingUrlGenerator;
use Tests\Support\Config\TestAssetConfig;
use Tests\Support\UrlGenerator\RoutingTestUrlGenerator;

/**
 * @internal
 */
final class PendingUrlGeneratorTest extends CIUnitTestCase
{
    private string $tempFilePath;
    private PendingAsset $pendingAsset;

    protected function setUp(): void
    {
        parent::setUp();
        Factories::injectMock('config', AssetConfig::class, new TestAssetConfig());
        Factories::injectMock('config', 'Asset', new TestAssetConfig());
        Services::reset();
        Services::routes()->loadRoutes();
        $tempFilePath = tempnam(sys_get_temp_dir(), 'pending_url_generator_');
        $this->assertIsString($tempFilePath);
        $this->tempFilePath = $tempFilePath;
        file_put_contents($this->tempFilePath, 'pending url content');
        $this->pendingAsset = PendingAsset::createFromFile($this->tempFilePath);
        $this->pendingAsset->setId('pending-url-id');
        $this->pendingAsset->usingFileName('pending-url.txt');
    }

    protected function tearDown(): void
    {
        if (is_file($this->tempFilePath)) {
            unlink($this->tempFilePath);
        }
        Factories::reset('config');
        parent::tearDown();
    }

    public function testGetUrlReturnsPreviewAndDownloadUrls(): void
    {
        $this->assertSame(
            'https://example.com/index.php/assets/pending/pending-url-id/pending-url.txt',
            $this->pendingAsset->getPreviewUrl(),
        );
        $this->assertSame(
            'https://example.com/index.php/assets/pending/pending-url-id/pending-url.txt?download=force',
            $this->pendingAsset->getDownloadUrl(),
        );
    }

    public function testGetUrlRelativeReturnsPathAndQuery(): void
    {
        $this->assertSame(
            '/index.php/assets/pending/pending-url-id/pending-url.txt',
            $this->pendingAsset->getPreviewUrlRelative(),
        );
        $this->assertSame(
            '/index.php/assets/pending/pending-url-id/pending-url.txt?download=force',
            $this->pendingAsset->getDownloadUrlRelative(),
        );
    }

    public function testJsonSerializeIncludesSafePendingUrls(): void
    {
        $data = $this->pendingAsset->jsonSerialize();

        $this->assertSame(
            'https://example.com/index.php/assets/pending/pending-url-id/pending-url.txt',
            $data['preview_url'],
        );
        $this->assertSame(
            'https://example.com/index.php/assets/pending/pending-url-id/pending-url.txt?download=force',
            $data['download_url'],
        );
    }

    public function testGetUrlReturnsEmptyStringWhenPendingAssetHasNoId(): void
    {
        $pendingAsset = PendingAsset::createFromFile($this->tempFilePath);

        $this->assertSame('', PendingUrlGenerator::create($pendingAsset)->getUrl());
    }

    public function testGetUrlReturnsEmptyStringWhenDefaultUrlGeneratorIsDisabled(): void
    {
        $config                      = new TestAssetConfig();
        $config->defaultUrlGenerator = null;
        Factories::injectMock('config', AssetConfig::class, $config);
        Factories::injectMock('config', 'Asset', $config);

        $this->assertSame('', $this->pendingAsset->getPreviewUrl());
    }

    public function testGetUrlRejectsUrlGeneratorWithoutPendingSupport(): void
    {
        $config                      = new TestAssetConfig();
        $config->defaultUrlGenerator = RoutingTestUrlGenerator::class;
        Factories::injectMock('config', AssetConfig::class, $config);
        Factories::injectMock('config', 'Asset', $config);

        try {
            $this->pendingAsset->getPreviewUrl();
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString(
                'must implement the PendingUrlGeneratorInterface',
                (string) $exception->errors[0],
            );

            return;
        }

        $this->fail('Expected pending URL generation to reject URL generators without pending support.');
    }
}
