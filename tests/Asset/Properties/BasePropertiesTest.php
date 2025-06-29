<?php

declare(strict_types=1);

namespace Tests\Asset\Properties;

use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;
use Maniaba\FileConnect\Asset\Properties\BaseProperties;

/**
 * Concrete implementation of BaseProperties for testing
 */
class TestProperties extends BaseProperties
{
    public static function getName(): string
    {
        return 'test_properties';
    }
}

/**
 * @internal
 */
final class BasePropertiesTest extends CIUnitTestCase
{
    /**
     * @var TestProperties
     */
    private $baseProperties;

    protected function setUp(): void
    {
        parent::setUp();

        // Create an instance of our concrete implementation
        $this->baseProperties = new TestProperties([]);
    }

    /**
     * Test that the constructor initializes properties
     */
    public function testConstructorInitializesProperties(): void
    {
        // Arrange
        $properties = ['key1' => 'value1', 'key2' => 'value2'];

        // Act
        $baseProperties = new TestProperties($properties);

        // Assert - we'll test this through the get method
        $this->assertSame('value1', $baseProperties->get('key1'));
        $this->assertSame('value2', $baseProperties->get('key2'));
    }

    /**
     * Test that jsonSerialize returns the properties with the name as key
     */
    public function testJsonSerializeReturnsPropertiesWithNameAsKey(): void
    {
        // Arrange
        $properties     = ['key1' => 'value1', 'key2' => 'value2'];
        $baseProperties = new TestProperties($properties);

        // Act
        $json = $baseProperties->jsonSerialize();

        // Assert
        $this->assertIsArray($json);
        $this->assertArrayHasKey('test_properties', $json);
        $this->assertSame($properties, $json['test_properties']);
    }

    /**
     * Test that set adds a property
     */
    public function testSetAddsProperty(): void
    {
        // Act
        $this->baseProperties->set('key', 'value');

        // Assert
        $this->assertSame('value', $this->baseProperties->get('key'));
    }

    /**
     * Test that get returns a property value
     */
    public function testGetReturnsPropertyValue(): void
    {
        // Arrange
        $this->baseProperties->set('key', 'value');

        // Act
        $value = $this->baseProperties->get('key');

        // Assert
        $this->assertSame('value', $value);
    }

    /**
     * Test that get returns null for non-existent property
     */
    public function testGetReturnsNullForNonExistentProperty(): void
    {
        // Act
        $value = $this->baseProperties->get('non_existent');

        // Assert
        $this->assertNull($value);
    }

    /**
     * Test that create returns an instance of the class
     */
    public function testCreateReturnsInstanceOfClass(): void
    {
        // Create a properties array with the test_properties key
        $properties = ['test_properties' => ['key' => 'value']];

        // Act
        $result = TestProperties::create($properties);

        // Assert
        $this->assertInstanceOf(TestProperties::class, $result);
        $this->assertSame('value', $result->get('key'));
    }

    /**
     * Test that create throws an exception when properties is not an array
     */
    public function testCreateThrowsExceptionWhenPropertiesIsNotAnArray(): void
    {
        // Create a properties array with the test_properties key as a string
        $properties = ['test_properties' => 'not an array'];

        // Act & Assert
        $this->expectException(InvalidArgumentException::class);
        TestProperties::create($properties);
    }

    /**
     * Test that create handles missing properties key
     */
    public function testCreateHandlesMissingPropertiesKey(): void
    {
        // Create a properties array without the test_properties key
        $properties = ['other_key' => ['key' => 'value']];

        // Act
        $result = TestProperties::create($properties);

        // Assert
        $this->assertInstanceOf(TestProperties::class, $result);
        $this->assertNull($result->get('key')); // Should be empty
    }
}
