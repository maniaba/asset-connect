<?php

declare(strict_types=1);

namespace Tests\Asset\Properties;

use CodeIgniter\Test\CIUnitTestCase;
use Maniaba\FileConnect\Asset\Properties\UserCustomProperties;

/**
 * @internal
 */
final class UserCustomPropertiesTest extends CIUnitTestCase
{
    /**
     * @var UserCustomProperties
     */
    private $properties;

    protected function setUp(): void
    {
        parent::setUp();
        $this->properties = new UserCustomProperties([]);
    }

    /**
     * Test that getName returns the correct name
     */
    public function testGetNameReturnsCorrectName(): void
    {
        $this->assertSame('user_custom', UserCustomProperties::getName());
    }

    /**
     * Test that create returns an instance of UserCustomProperties
     */
    public function testCreateReturnsInstanceOfUserCustomProperties(): void
    {
        // Arrange
        $properties = ['user_custom' => ['key' => 'value']];

        // Act
        $result = UserCustomProperties::create($properties);

        // Assert
        $this->assertInstanceOf(UserCustomProperties::class, $result);
        $this->assertSame('value', $result->get('key'));
    }

    /**
     * Test that jsonSerialize returns the properties with the correct name as key
     */
    public function testJsonSerializeReturnsPropertiesWithCorrectNameAsKey(): void
    {
        // Arrange
        $properties           = ['key1' => 'value1', 'key2' => 'value2'];
        $userCustomProperties = new UserCustomProperties($properties);

        // Act
        $json = $userCustomProperties->jsonSerialize();

        // Assert
        $this->assertIsArray($json);
        $this->assertArrayHasKey('user_custom', $json);
        $this->assertSame($properties, $json['user_custom']);
    }
}
