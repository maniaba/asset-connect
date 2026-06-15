<?php

declare(strict_types=1);

namespace Tests\PathGenerator;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Maniaba\AssetConnect\AssetCollection\AssetCollection;
use Maniaba\AssetConnect\PathGenerator\Interfaces\PathGeneratorInterface;
use Maniaba\AssetConnect\PathGenerator\PathGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;

/**
 * @internal
 */
final class PathGeneratorTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    private PathGenerator $pathGenerator;
    private MockObject|PathGeneratorInterface $mockPathGeneratorInterface;

    protected function setUp(): void
    {
        parent::setUp();
        $reflectionClass                  = new ReflectionClass(AssetCollection::class);
        $assetCollection                  = $reflectionClass->newInstanceWithoutConstructor();
        $this->mockPathGeneratorInterface = $this->createMock(PathGeneratorInterface::class);
        $this->setPrivateProperty($assetCollection, 'pathGenerator', $this->mockPathGeneratorInterface);
        $this->pathGenerator = new PathGenerator($assetCollection);
    }

    public function testGetFileRelativePath(): void
    {
        $this->mockPathGeneratorInterface->expects($this->once())
            ->method('getFileRelativePath')
            ->willReturn('relative/path');

        $this->assertSame('relative/path/', $this->pathGenerator->getFileRelativePath());
    }

    public function testGetPath(): void
    {
        $this->mockPathGeneratorInterface->expects($this->once())
            ->method('getPath')
            ->willReturn('path/to/file/');

        $this->assertSame('path/to/file/', $this->pathGenerator->getPath());
    }

    public function testGetPathNormalizesBackslashesAndLeadingSlash(): void
    {
        $this->mockPathGeneratorInterface->expects($this->once())
            ->method('getPath')
            ->willReturn('\\path\\to\\file');

        $this->assertSame('path/to/file/', $this->pathGenerator->getPath());
    }

    public function testGetFileRelativePathForVariants(): void
    {
        $this->mockPathGeneratorInterface->expects($this->once())
            ->method('getFileRelativePathForVariants')
            ->willReturn('relative/path/variants');

        $this->assertSame('relative/path/variants/', $this->pathGenerator->getFileRelativePathForVariants());
    }

    public function testGetPathForVariants(): void
    {
        $this->mockPathGeneratorInterface->expects($this->once())
            ->method('getPathForVariants')
            ->willReturn('/path/to/file/variants');

        $this->assertSame('path/to/file/variants/', $this->pathGenerator->getPathForVariants());
    }
}
