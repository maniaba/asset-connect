<?php

declare(strict_types=1);

namespace Tests\Utils;

use CodeIgniter\Test\CIUnitTestCase;
use Maniaba\FileConnect\Utils\Format;

/**
 * @internal
 */
final class FormatTest extends CIUnitTestCase
{
    /**
     * Test formatBytesHumanReadable with 0 bytes
     */
    public function testFormatBytesHumanReadableWithZeroBytes(): void
    {
        // Act
        $result = Format::formatBytesHumanReadable(0);

        // Assert
        $this->assertSame('0 B', $result);
    }

    /**
     * Test formatBytesHumanReadable with bytes (less than 1 KB)
     */
    public function testFormatBytesHumanReadableWithBytes(): void
    {
        // Act
        $result = Format::formatBytesHumanReadable(512);

        // Assert
        $this->assertSame('512 B', $result);
    }

    /**
     * Test formatBytesHumanReadable with kilobytes
     */
    public function testFormatBytesHumanReadableWithKilobytes(): void
    {
        // Act
        $result = Format::formatBytesHumanReadable(1536); // 1.5 KB

        // Assert
        $this->assertSame('1.5 KB', $result);
    }

    /**
     * Test formatBytesHumanReadable with megabytes
     */
    public function testFormatBytesHumanReadableWithMegabytes(): void
    {
        // Act
        $result = Format::formatBytesHumanReadable(2097152); // 2 MB

        // Assert
        $this->assertSame('2 MB', $result);
    }

    /**
     * Test formatBytesHumanReadable with gigabytes
     */
    public function testFormatBytesHumanReadableWithGigabytes(): void
    {
        // Act
        $result = Format::formatBytesHumanReadable(3221225472); // 3 GB

        // Assert
        $this->assertSame('3 GB', $result);
    }

    /**
     * Test formatBytesHumanReadable with terabytes
     */
    public function testFormatBytesHumanReadableWithTerabytes(): void
    {
        // Act
        $result = Format::formatBytesHumanReadable(4398046511104); // 4 TB

        // Assert
        $this->assertSame('4 TB', $result);
    }

    /**
     * Test formatBytesHumanReadable with petabytes
     */
    public function testFormatBytesHumanReadableWithPetabytes(): void
    {
        // Act
        $result = Format::formatBytesHumanReadable(5629499534213120); // 5 PB

        // Assert
        $this->assertSame('5 PB', $result);
    }

    /**
     * Test formatBytesHumanReadable with custom precision
     */
    public function testFormatBytesHumanReadableWithCustomPrecision(): void
    {
        // Act
        $result = Format::formatBytesHumanReadable(1572864, 3); // 1.5 MB with 3 decimal places

        // Assert
        $this->assertSame('1.5 MB', $result);
    }

    /**
     * Test formatBytesHumanReadable with negative bytes (should be treated as 0)
     */
    public function testFormatBytesHumanReadableWithNegativeBytes(): void
    {
        // Act
        $result = Format::formatBytesHumanReadable(-1024);

        // Assert
        $this->assertSame('0 B', $result);
    }
}
