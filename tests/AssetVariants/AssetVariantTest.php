<?php

declare(strict_types=1);

namespace Tests\AssetVariants;

use CodeIgniter\Config\Factories;
use CodeIgniter\Test\CIUnitTestCase;
use Maniaba\AssetConnect\AssetVariants\AssetVariant;
use Maniaba\AssetConnect\Config\Asset;
use Maniaba\AssetConnect\Exceptions\FileVariantException;
use Tests\Support\Config\TestAssetConfig;

/**
 * @internal
 */
final class AssetVariantTest extends CIUnitTestCase
{
    private AssetVariant $assetVariant;

    protected function setUp(): void
    {
        parent::setUp();
        Factories::injectMock('config', Asset::class, new TestAssetConfig());

        $this->assetVariant = new AssetVariant([
            'storage' => 'public',
        ]);
    }

    public function testWriteFileSuccessfully(): void
    {
        $this->assetVariant->path = 'variants/write_method/file.txt';
        $data                     = 'file content';

        $result = $this->assetVariant->writeFile($data);

        $this->assertTrue($result);
        $this->assertSame(12, $this->assetVariant->size);
        $this->assertTrue($this->assetVariant->processed);
        $this->assertFileExists(HOMEPATH . 'build/asset-connect/public/variants/write_method/file.txt');
    }

    public function testGetRelativePathReturnsCorrectPath(): void
    {
        $this->assetVariant->path = 'path/to/file.jpg';

        $method = $this->getPrivateMethodInvoker($this->assetVariant, 'getRelativePath');

        $result = $method($this->assetVariant);

        $this->assertSame('/path/to/file.jpg', $result);
    }

    public function testGetRelativePathThrowsExceptionWhenPathIsNotSet(): void
    {
        $this->assetVariant->path = '';

        $invoker = $this->getPrivateMethodInvoker($this->assetVariant, 'getRelativePath');

        $this->expectException(FileVariantException::class);
        $invoker();
    }

    public function testGetRelativePathForUrlReturnsCorrectUrlPath(): void
    {
        $this->assetVariant->path = 'path/to/file.jpg';

        $invoker = $this->getPrivateMethodInvoker($this->assetVariant, 'getRelativePathForUrl');

        $result = $invoker();

        $this->assertSame('/path/to/file.jpg', $result);
    }

    public function testGetRelativePathForUrlReplacesBackslashesWithForwardSlashes(): void
    {
        $this->assetVariant->path = 'path\\to\\file.jpg';

        $getRelativePathMethod = $this->getPrivateMethodInvoker($this->assetVariant, 'getRelativePath');
        $relativePath          = (string) $getRelativePathMethod();

        $this->assertStringNotContainsString('\\', $relativePath);

        $getRelativePathForUrlMethod = $this->getPrivateMethodInvoker($this->assetVariant, 'getRelativePathForUrl');

        $result = (string) $getRelativePathForUrlMethod();

        $this->assertStringNotContainsString('\\', $result);
        $this->assertStringContainsString('/', $result);
    }
}
