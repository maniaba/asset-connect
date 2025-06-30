<?php

declare(strict_types=1);

namespace Tests\Asset;

use CodeIgniter\Test\CIUnitTestCase;
use Maniaba\FileConnect\Asset\AssetMetadata;

/**
 * @internal
 */
final class PropertiesTest extends CIUnitTestCase
{
    private AssetMetadata $properties;

    protected function setUp(): void
    {
        parent::setUp();
        $this->properties = new AssetMetadata();
    }

    /**
     * Test that the constructor creates UserCustomProperties and FileVariantProperties
     */
    public function testConstructorCreatesProperties(): void
    {
        // Assert
        $this->assertNotNull($this->properties->userCustom);
        $this->assertNotNull($this->properties->fileVariant);
    }

    /**
     * Test that the constructor initializes properties with provided values
     */
    public function testConstructorInitializesPropertiesWithValues(): void
    {
        // Arrange
        $values = [
            'user_custom' => [
                'name'        => 'Test Name',
                'description' => 'Test Description',
            ],
            'file_variants' => [
                'thumbnail' => 'thumbnail.jpg',
                'medium'    => 'medium.jpg',
            ],
        ];

        // Act
        $properties = new AssetMetadata($values);

        // Assert
        $this->assertSame('Test Name', $properties->userCustom->get('name'));
        $this->assertSame('Test Description', $properties->userCustom->get('description'));
        $this->assertSame('thumbnail.jpg', $properties->fileVariant->get('thumbnail'));
        $this->assertSame('medium.jpg', $properties->fileVariant->get('medium'));
    }

    /**
     * Test that jsonSerialize returns the combined properties
     */
    public function testJsonSerializeReturnsCombinedProperties(): void
    {
        // Arrange
        $values = [
            'user_custom' => [
                'name'        => 'Test Name',
                'description' => 'Test Description',
            ],
            'file_variants' => [
                'thumbnail' => 'thumbnail.jpg',
                'medium'    => 'medium.jpg',
            ],
        ];
        $properties = new AssetMetadata($values);

        // Act
        $json = $properties->jsonSerialize();

        // Assert
        $this->assertArrayHasKey('user_custom', $json);
        $this->assertArrayHasKey('file_variants', $json);
        $this->assertSame($values['user_custom'], $json['user_custom']);
        $this->assertSame($values['file_variants'], $json['file_variants']);
    }

    /**
     * Test that __toString returns a JSON string
     */
    public function testToStringReturnsJsonString(): void
    {
        // Arrange
        $values = [
            'user_custom' => [
                'name'        => 'Test Name',
                'description' => 'Test Description',
            ],
            'file_variants' => [
                'thumbnail' => 'thumbnail.jpg',
                'medium'    => 'medium.jpg',
            ],
        ];
        $properties = new AssetMetadata($values);

        // Act
        $string = (string) $properties;

        // Assert
        $decoded = json_decode($string, true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('user_custom', $decoded);
        $this->assertArrayHasKey('file_variants', $decoded);
        $this->assertSame($values['user_custom'], $decoded['user_custom']);
        $this->assertSame($values['file_variants'], $decoded['file_variants']);
    }
}
