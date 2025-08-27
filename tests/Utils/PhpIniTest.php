<?php

declare(strict_types=1);

namespace Tests\Utils;

use CodeIgniter\Test\CIUnitTestCase;
use Maniaba\AssetConnect\Utils\PhpIni;

/**
 * @internal
 */
final class PhpIniTest extends CIUnitTestCase
{
    /**
     * Test shorthandToBytes method with bytes
     */
    public function testShorthandToBytesWithBytes(): void
    {
        // Arrange
        $value = '1024';

        // Act
        $result = $this->invokePrivateMethod(PhpIni::class, 'shorthandToBytes', [$value]);

        // Assert
        $this->assertSame(1024, $result);
    }

    /**
     * Test shorthandToBytes method with kilobytes
     */
    public function testShorthandToBytesWithKilobytes(): void
    {
        // Arrange
        $value = '2K';

        // Act
        $result = $this->invokePrivateMethod(PhpIni::class, 'shorthandToBytes', [$value]);

        // Assert
        $this->assertSame(2 * 1024, $result);
    }

    /**
     * Test shorthandToBytes method with megabytes
     */
    public function testShorthandToBytesWithMegabytes(): void
    {
        // Arrange
        $value = '3M';

        // Act
        $result = $this->invokePrivateMethod(PhpIni::class, 'shorthandToBytes', [$value]);

        // Assert
        $this->assertSame(3 * 1024 * 1024, $result);
    }

    /**
     * Test shorthandToBytes method with gigabytes
     */
    public function testShorthandToBytesWithGigabytes(): void
    {
        // Arrange
        $value = '4G';

        // Act
        $result = $this->invokePrivateMethod(PhpIni::class, 'shorthandToBytes', [$value]);

        // Assert
        $this->assertSame(4 * 1024 * 1024 * 1024, $result);
    }

    /**
     * Test shorthandToBytes method with lowercase units
     */
    public function testShorthandToBytesWithLowercaseUnits(): void
    {
        // Arrange
        $values   = ['2k', '3m', '4g'];
        $expected = [
            2 * 1024,
            3 * 1024 * 1024,
            4 * 1024 * 1024 * 1024,
        ];

        // Act & Assert
        foreach ($values as $index => $value) {
            $result = $this->invokePrivateMethod(PhpIni::class, 'shorthandToBytes', [$value]);
            $this->assertSame($expected[$index], $result);
        }
    }

    /**
     * Test shorthandToBytes method with whitespace
     */
    public function testShorthandToBytesWithWhitespace(): void
    {
        // Arrange
        $value = ' 5M ';

        // Act
        $result = $this->invokePrivateMethod(PhpIni::class, 'shorthandToBytes', [$value]);

        // Assert
        $this->assertSame(5 * 1024 * 1024, $result);
    }

    /**
     * Helper method to invoke private methods using ReflectionHelper
     *
     * @param string $class      The class name
     * @param string $methodName The method name
     * @param array  $parameters The parameters to pass to the method
     *
     * @return mixed The result of the method call
     */
    private function invokePrivateMethod(string $class, string $methodName, array $parameters = []): mixed
    {
        $invoker = $this->getPrivateMethodInvoker($class, $methodName);

        return $invoker(...$parameters);
    }
}
