<?php

declare(strict_types=1);

namespace Tests\PathGenerator;

use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;
use Maniaba\AssetConnect\PathGenerator\PathGeneratorHelper;

/**
 * @internal
 */
final class PathGeneratorHelperTest extends CIUnitTestCase
{
    private PathGeneratorHelper $helper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->helper = new PathGeneratorHelper();
    }

    /**
     * Test getUniqueId method without more entropy
     */
    public function testGetUniqueIdWithoutMoreEntropy(): void
    {
        // Arrange
        $moreEntropy = false;

        // Act
        $uniqueId = $this->helper->getUniqueId($moreEntropy);

        // Assert
        $this->assertIsString($uniqueId);
        $this->assertNotEmpty($uniqueId);
        $this->assertStringContainsString('_', $uniqueId);
    }

    /**
     * Test getUniqueId method with more entropy
     */
    public function testGetUniqueIdWithMoreEntropy(): void
    {
        // Arrange
        $moreEntropy = true;

        // Act
        $uniqueId = $this->helper->getUniqueId($moreEntropy);

        // Assert
        $this->assertIsString($uniqueId);
        $this->assertNotEmpty($uniqueId);
        $this->assertSame(64, strlen($uniqueId)); // SHA-256 hash is 64 characters long
    }

    public function testGetRandomId(): void
    {
        $randomId = $this->helper->getRandomId();

        $this->assertMatchesRegularExpression('/^[a-f0-9]{20}$/', $randomId);
    }

    public function testGetRandomIdWithCustomLength(): void
    {
        $randomId = $this->helper->getRandomId(9);

        $this->assertMatchesRegularExpression('/^[a-f0-9]{9}$/', $randomId);
    }

    public function testGetRandomIdRejectsInvalidLength(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Random ID length must be greater than zero.');

        $this->helper->getRandomId(0);
    }

    public function testGetDate(): void
    {
        Time::setTestNow('2025-01-01 12:00:00.123456');

        $this->assertSame('2025-01-01', $this->helper->getDate());
    }

    /**
     * Test getDateTime method
     */
    public function testGetDateTime(): void
    {
        // Mock the date function to return a fixed date
        Time::setTestNow('2025-01-01 12:00:00.123456');

        $expectedDateTime = Time::now()->format('Y-m-d') . DIRECTORY_SEPARATOR . Time::now()->format('His.u');

        // Act
        $dateTime = $this->helper->getDateTime();

        // Assert
        $this->assertSame($expectedDateTime, $dateTime);
    }

    /**
     * Test getTime method
     */
    public function testGetTime(): void
    {
        Time::setTestNow('2025-01-01 12:00:00.123456');

        $expected = Time::now()->format('His.u');

        // Act
        $time = $this->helper->getTime();

        // Assert
        $this->assertSame($expected, $time);
    }

    /**
     * Test getPathString method
     */
    public function testGetPathString(): void
    {
        // Arrange
        $segment1 = 'segment1';
        $segment2 = 'segment2';
        $segment3 = 'segment3';

        // Act
        $pathString = $this->helper->getPathString($segment1, $segment2, $segment3);

        // Assert
        $expectedPath = 'segment1' . DIRECTORY_SEPARATOR . 'segment2' . DIRECTORY_SEPARATOR . 'segment3';
        $this->assertSame($expectedPath, $pathString);
    }

    /**
     * Test getPathString method with empty segments
     */
    public function testGetPathStringWithEmptySegments(): void
    {
        // Act
        $pathString = $this->helper->getPathString();

        // Assert
        $this->assertSame('', $pathString);
    }

    /**
     * Test getPathString method with a single segment
     */
    public function testGetPathStringWithSingleSegment(): void
    {
        // Arrange
        $segment = 'segment';

        // Act
        $pathString = $this->helper->getPathString($segment);

        // Assert
        $this->assertSame($segment, $pathString);
    }
}
