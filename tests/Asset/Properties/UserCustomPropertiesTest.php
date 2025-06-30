<?php

declare(strict_types=1);

namespace Tests\Asset\Properties;

use CodeIgniter\Test\CIUnitTestCase;
use Maniaba\FileConnect\Asset\Properties\UserCustomProperty;

/**
 * @internal
 */
final class UserCustomPropertiesTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test that getName returns the correct name
     */
    public function testGetNameReturnsCorrectName(): void
    {
        $this->assertSame('user_custom', UserCustomProperty::getName());
    }

    /**
     * Test that create returns an instance of UserCustomProperties
     */
    public function testCreateReturnsInstanceOfUserCustomProperties(): void
    {
        // Arrange
        $properties = ['user_custom' => ['key' => 'value']];

        // Act
        $result = UserCustomProperty::create($properties);

        // Assert
        $this->assertInstanceOf(UserCustomProperty::class, $result);
        $this->assertSame('value', $result->get('key'));
    }

    /**
     * Test that jsonSerialize returns the properties with the correct name as key
     */
    public function testJsonSerializeReturnsPropertiesWithCorrectNameAsKey(): void
    {
        // Arrange
        $properties           = ['key1' => 'value1', 'key2' => 'value2'];
        $userCustomProperties = new UserCustomProperty($properties);

        // Act
        $json = $userCustomProperties->jsonSerialize();

        // Assert
        $this->assertIsArray($json);
        $this->assertArrayHasKey('user_custom', $json);
        $this->assertSame($properties, $json['user_custom']);
    }
}
