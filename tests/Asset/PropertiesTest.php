<?php

declare(strict_types=1);

namespace Tests\Asset;

use CodeIgniter\Test\CIUnitTestCase;
use Maniaba\AssetConnect\Asset\AssetMetadata;

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
     * Test that the constructor creates metadata property objects
     */
    public function testConstructorCreatesProperties(): void
    {
        // Assert
        $this->assertSame([], $this->properties->userCustom->getAll());
        $this->assertSame([], $this->properties->internal->getAll());
        $this->assertSame([], $this->properties->assetVariant->getAll());
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
            'internal' => [
                'processing_job_id' => 'job-123',
            ],
            'asset_variants' => [
                'thumbnail' => 'thumbnail.jpg',
                'medium'    => 'medium.jpg',
            ],
        ];

        // Act
        $properties = new AssetMetadata($values);

        // Assert
        $this->assertSame('Test Name', $properties->userCustom->get('name'));
        $this->assertSame('Test Description', $properties->userCustom->get('description'));
        $this->assertSame('job-123', $properties->internal->get('processing_job_id'));
        $this->assertSame('thumbnail.jpg', $properties->assetVariant->get('thumbnail'));
        $this->assertSame('medium.jpg', $properties->assetVariant->get('medium'));
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
            'internal' => [
                'processing_job_id' => 'job-123',
            ],
            'asset_variants' => [
                'thumbnail' => 'thumbnail.jpg',
                'medium'    => 'medium.jpg',
            ],
        ];
        $properties = new AssetMetadata($values);

        // Act
        $json = $properties->jsonSerialize();

        // Assert
        $this->assertArrayHasKey('user_custom', $json);
        $this->assertArrayHasKey('internal', $json);
        $this->assertArrayHasKey('asset_variants', $json);
        $this->assertSame($values['user_custom'], $json['user_custom']);
        $this->assertSame($values['internal'], $json['internal']);
        $this->assertSame($values['asset_variants'], $json['asset_variants']);
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
            'internal' => [
                'processing_job_id' => 'job-123',
            ],
            'asset_variants' => [
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
        $this->assertArrayHasKey('internal', $decoded);
        $this->assertArrayHasKey('asset_variants', $decoded);
        $this->assertSame($values['user_custom'], $decoded['user_custom']);
        $this->assertSame($values['internal'], $decoded['internal']);
        $this->assertSame($values['asset_variants'], $decoded['asset_variants']);
    }
}
