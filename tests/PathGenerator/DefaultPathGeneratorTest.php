<?php

declare(strict_types=1);

namespace Tests\PathGenerator;

use CodeIgniter\Test\CIUnitTestCase;
use Maniaba\AssetConnect\Asset\Interfaces\AssetCollectionGetterInterface;
use Maniaba\AssetConnect\PathGenerator\DefaultPathGenerator;
use Maniaba\AssetConnect\PathGenerator\PathGeneratorHelper;
use PHPUnit\Framework\MockObject\Stub;

/**
 * @internal
 */
final class DefaultPathGeneratorTest extends CIUnitTestCase
{
    private DefaultPathGenerator $pathGenerator;
    private PathGeneratorHelper $helper;
    private AssetCollectionGetterInterface&Stub $mockCollection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pathGenerator  = new DefaultPathGenerator();
        $this->helper         = new PathGeneratorHelper();
        $this->mockCollection = $this->createStub(AssetCollectionGetterInterface::class);
    }

    public function testGetFileRelativePath(): void
    {
        $this->setPrivateProperty($this->pathGenerator, 'fileRelativePath', 'assets/2023-01-01/120000.000000/');

        $path = $this->pathGenerator->getFileRelativePath($this->helper, $this->mockCollection);

        $this->assertSame('assets/2023-01-01/120000.000000/', $path);
    }

    public function testGetPath(): void
    {
        $this->setPrivateProperty($this->pathGenerator, 'fileRelativePath', 'assets/2023-01-01/120000.000000/');

        $path = $this->pathGenerator->getPath($this->helper, $this->mockCollection);

        $this->assertSame('assets/2023-01-01/120000.000000/', $path);
    }

    public function testGetFileRelativePathForVariants(): void
    {
        $this->setPrivateProperty($this->pathGenerator, 'fileRelativePath', 'assets/2023-01-01/120000.000000/');

        $path = $this->pathGenerator->getFileRelativePathForVariants($this->helper, $this->mockCollection);

        $this->assertSame('assets/2023-01-01/120000.000000/variants/', $path);
    }

    public function testGetPathForVariants(): void
    {
        $this->setPrivateProperty($this->pathGenerator, 'fileRelativePath', 'assets/2023-01-01/120000.000000/');

        $path = $this->pathGenerator->getPathForVariants($this->helper, $this->mockCollection);

        $this->assertSame('assets/2023-01-01/120000.000000/variants/', $path);
    }
}
