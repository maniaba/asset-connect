<?php

declare(strict_types=1);

namespace Tests\Asset\Properties;

use CodeIgniter\Test\CIUnitTestCase;
use Maniaba\FileConnect\Asset\Properties\AssetVariantProperty;
use Maniaba\FileConnect\AssetVariants\AssetVariant;
use Maniaba\FileConnect\Exceptions\InvalidArgumentException;
use Override;
use ReflectionClass;

/**
 * @internal
 */
final class AssetVariantPropertyTest extends CIUnitTestCase
{
    private AssetVariantProperty $assetVariantProperty;
    private AssetVariant $variant;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->assetVariantProperty = new AssetVariantProperty([]);
        $this->variant              = new AssetVariant(['name' => 'test_variant', 'path' => '/path/to/variant']);
    }

    /**
     * Test that getName returns the correct name
     */
    public function testGetNameReturnsCorrectName(): void
    {
        $this->assertSame('asset_variants', AssetVariantProperty::getName());
    }

    /**
     * Test that create returns an instance of AssetVariantProperty
     */
    public function testCreateReturnsInstanceOfAssetVariantProperty(): void
    {
        // Arrange
        $properties = ['asset_variants' => ['key' => 'value']];

        // Act
        $result = AssetVariantProperty::create($properties);

        // Assert
        $this->assertInstanceOf(AssetVariantProperty::class, $result);
        $this->assertSame('value', $result->get('key'));
    }

    /**
     * Test addAssetVariant adds a variant
     */
    public function testAddAssetVariantAddsVariant(): void
    {
        // Act
        $this->assetVariantProperty->addAssetVariant($this->variant);

        // Assert
        $this->assertSame($this->variant, $this->assetVariantProperty->getAssetVariant('test_variant'));
    }

    /**
     * Test updateAssetVariant updates an existing variant
     */
    public function testUpdateAssetVariantUpdatesExistingVariant(): void
    {
        // Arrange
        $this->assetVariantProperty->addAssetVariant($this->variant);

        // Create a new variant with the same name
        $newVariant = new AssetVariant(['name' => 'test_variant', 'path' => '/path/to/new/variant']);

        // Act
        $this->assetVariantProperty->updateAssetVariant($newVariant);

        // Assert
        $this->assertSame($newVariant, $this->assetVariantProperty->getAssetVariant('test_variant'));
    }

    /**
     * Test updateAssetVariant throws exception for non-existent variant
     */
    public function testUpdateAssetVariantThrowsExceptionForNonExistentVariant(): void
    {
        // Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->assetVariantProperty->updateAssetVariant($this->variant);
    }

    /**
     * Test getAssetVariant returns null for non-existent variant
     */
    public function testGetAssetVariantReturnsNullForNonExistentVariant(): void
    {
        // Act
        $result = $this->assetVariantProperty->getAssetVariant('non_existent');

        // Assert
        $this->assertNotInstanceOf(AssetVariant::class, $result);
    }

    /**
     * Test getAssetVariant returns AssetVariant instance for array data
     */
    public function testGetAssetVariantReturnsAssetVariantInstanceForArrayData(): void
    {
        // Arrange
        $variantData = ['name' => 'test_variant', 'path' => '/path/to/variant'];
        $this->assetVariantProperty->set('test_variant', $variantData);

        // Act
        $result = $this->assetVariantProperty->getAssetVariant('test_variant');

        // Assert
        $this->assertInstanceOf(AssetVariant::class, $result);
        $this->assertSame('test_variant', $result->name);
    }

    /**
     * Test getVariants returns all variants
     */
    public function testGetVariantsReturnsAllVariants(): void
    {
        // Arrange
        $this->assetVariantProperty->addAssetVariant($this->variant);

        // Create a second variant
        $secondVariant = new AssetVariant(['name' => 'second_variant', 'path' => '/path/to/second/variant']);
        $this->assetVariantProperty->addAssetVariant($secondVariant);

        // Act
        $variants = $this->assetVariantProperty->getVariants();

        // Assert
        $this->assertCount(2, $variants);
        $this->assertSame($this->variant, $variants['test_variant']);
        $this->assertSame($secondVariant, $variants['second_variant']);
    }

    /**
     * Test getVariants converts array data to AssetVariant instances
     */
    public function testGetVariantsConvertsArrayDataToAssetVariantInstances(): void
    {
        // Arrange
        $variantData = ['name' => 'test_variant', 'path' => '/path/to/variant'];

        // Use reflection to set the properties directly
        $reflection         = new ReflectionClass($this->assetVariantProperty);
        $propertiesProperty = $reflection->getProperty('properties');
        $propertiesProperty->setAccessible(true);
        $propertiesProperty->setValue($this->assetVariantProperty, ['test_variant' => $variantData]);

        // Act
        $variants = $this->assetVariantProperty->getVariants();

        // Assert
        $this->assertCount(1, $variants);
        $this->assertInstanceOf(AssetVariant::class, $variants['test_variant']);
    }

    /**
     * Test hasAssetVariant returns true for existing variant
     */
    public function testHasAssetVariantReturnsTrueForExistingVariant(): void
    {
        // Arrange
        $this->assetVariantProperty->addAssetVariant($this->variant);

        // Act
        $result = $this->assetVariantProperty->hasAssetVariant('test_variant');

        // Assert
        $this->assertTrue($result);
    }

    /**
     * Test hasAssetVariant returns false for non-existent variant
     */
    public function testHasAssetVariantReturnsFalseForNonExistentVariant(): void
    {
        // Act
        $result = $this->assetVariantProperty->hasAssetVariant('non_existent');

        // Assert
        $this->assertFalse($result);
    }

    /**
     * Test removeAssetVariant removes an existing variant
     */
    public function testRemoveAssetVariantRemovesExistingVariant(): void
    {
        // Arrange
        $this->assetVariantProperty->addAssetVariant($this->variant);

        // Act
        $this->assetVariantProperty->removeAssetVariant('test_variant');

        // Assert
        $this->assertFalse($this->assetVariantProperty->hasAssetVariant('test_variant'));
    }

    /**
     * Test removeAssetVariant does nothing for non-existent variant
     */
    public function testRemoveAssetVariantDoesNothingForNonExistentVariant(): void
    {
        // Act
        $this->assetVariantProperty->removeAssetVariant('non_existent');

        // Assert - no exception should be thrown
        $this->assertTrue(true);
    }

    /**
     * Test hasVariants returns true when variants exist
     */
    public function testHasVariantsReturnsTrueWhenVariantsExist(): void
    {
        // Arrange
        $this->assetVariantProperty->addAssetVariant($this->variant);

        // Act
        $result = $this->assetVariantProperty->hasVariants();

        // Assert
        $this->assertTrue($result);
    }

    /**
     * Test hasVariants returns false when no variants exist
     */
    public function testHasVariantsReturnsFalseWhenNoVariantsExist(): void
    {
        // Act
        $result = $this->assetVariantProperty->hasVariants();

        // Assert
        $this->assertFalse($result);
    }
}
