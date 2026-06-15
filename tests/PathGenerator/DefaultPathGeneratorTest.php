<?php

declare(strict_types=1);

namespace Tests\PathGenerator;

use CodeIgniter\I18n\Time;
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
    private const string FIXED_RELATIVE_PATH = '2023-01-01/9f86d081884c7d659a2f/';

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
        $this->setPrivateProperty($this->pathGenerator, 'fileRelativePath', self::FIXED_RELATIVE_PATH);

        $path = $this->pathGenerator->getFileRelativePath($this->helper, $this->mockCollection);

        $this->assertSame(self::FIXED_RELATIVE_PATH, $path);
    }

    public function testGetPath(): void
    {
        $this->setPrivateProperty($this->pathGenerator, 'fileRelativePath', self::FIXED_RELATIVE_PATH);

        $path = $this->pathGenerator->getPath($this->helper, $this->mockCollection);

        $this->assertSame(self::FIXED_RELATIVE_PATH, $path);
    }

    public function testGetFileRelativePathForVariants(): void
    {
        $this->setPrivateProperty($this->pathGenerator, 'fileRelativePath', self::FIXED_RELATIVE_PATH);

        $path = $this->pathGenerator->getFileRelativePathForVariants($this->helper, $this->mockCollection);

        $this->assertSame(self::FIXED_RELATIVE_PATH . 'variants/', $path);
    }

    public function testGetPathForVariants(): void
    {
        $this->setPrivateProperty($this->pathGenerator, 'fileRelativePath', self::FIXED_RELATIVE_PATH);

        $path = $this->pathGenerator->getPathForVariants($this->helper, $this->mockCollection);

        $this->assertSame(self::FIXED_RELATIVE_PATH . 'variants/', $path);
    }

    public function testGeneratedPathsUseRandomSegmentToAvoidCollisions(): void
    {
        Time::setTestNow('2025-01-01 12:00:00.123456');

        $firstPath  = (new DefaultPathGenerator())->getFileRelativePath($this->helper, $this->mockCollection);
        $secondPath = (new DefaultPathGenerator())->getFileRelativePath($this->helper, $this->mockCollection);

        $this->assertMatchesRegularExpression('#^2025-01-01/[a-f0-9]{20}/$#', $firstPath);
        $this->assertMatchesRegularExpression('#^2025-01-01/[a-f0-9]{20}/$#', $secondPath);
        $this->assertNotSame($firstPath, $secondPath);
    }

    public function testGeneratedPathIsMemoizedForVariants(): void
    {
        Time::setTestNow('2025-01-01 12:00:00.123456');

        $path        = $this->pathGenerator->getFileRelativePath($this->helper, $this->mockCollection);
        $variantPath = $this->pathGenerator->getFileRelativePathForVariants($this->helper, $this->mockCollection);

        $this->assertSame($path . 'variants/', $variantPath);
    }
}
