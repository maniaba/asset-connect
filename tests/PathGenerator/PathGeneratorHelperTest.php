<?php

declare(strict_types=1);

namespace Tests\PathGenerator;

use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use Maniaba\FileConnect\PathGenerator\PathGeneratorHelper;

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

    /**
     * Test getDateTime method
     */
    public function testGetDateTime(): void
    {
        // Mock the date function to return a fixed date
        Time::setTestNow('2025-01-01 12:00:00');

        $expectedDateTime = Time::now()->format('Y-m-d') . DIRECTORY_SEPARATOR . Time::now()->format('His.U');

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
        Time::setTestNow('2025-01-01 12:00:00');

        $expected = Time::now()->format('His.U');

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
