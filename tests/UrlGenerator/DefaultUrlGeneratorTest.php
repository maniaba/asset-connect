<?php

declare(strict_types=1);

namespace Tests\UrlGenerator;

use CodeIgniter\Router\RouteCollection;
use CodeIgniter\Test\CIUnitTestCase;
use Maniaba\AssetConnect\Asset\Asset;
use Maniaba\AssetConnect\AssetVariants\AssetVariant;
use Maniaba\AssetConnect\Controllers\AssetConnectController;
use Maniaba\AssetConnect\UrlGenerator\DefaultUrlGenerator;
use Override;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @internal
 */
final class DefaultUrlGeneratorTest extends CIUnitTestCase
{
    private MockObject|RouteCollection $mockRoutes;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        // Mock the RouteCollection
        $this->mockRoutes = $this->createMock(RouteCollection::class);
    }

    /**
     * Test routes method
     */
    public function testRoutes(): void
    {
        // Arrange
        $groupCallback = null;

        // Setup expectations for the group method
        $this->mockRoutes->expects($this->once())
            ->method('group')
            ->with(
                'assets',
                $this->callback(static function ($callback) use (&$groupCallback) {
                    $groupCallback = $callback;

                    return true;
                }),
            );

        // Act
        DefaultUrlGenerator::routes($this->mockRoutes);

        // Assert
        $this->assertIsCallable($groupCallback);

        // Call the group callback with a mock RouteCollection to verify it sets up the routes correctly
        $mockGroupRoutes = $this->createMock(RouteCollection::class);

        // Setup expectations for the get method to be called 4 times for the 4 routes
        // Define the expected parameters for each call
        $expectedCalls = [
            [
                'pattern' => '(:num)/(:segment)',
                'handler' => [AssetConnectController::class, 'show/$1/$3'],
                'options' => ['priority' => 100, 'as' => 'asset-connect.show'],
            ],
            [
                'pattern' => '(:num)/variant/(:segment)/(:segment)',
                'handler' => [AssetConnectController::class, 'show/$1/$2'],
                'options' => ['priority' => 100, 'as' => 'asset-connect.show_variant'],
            ],
            [
                'pattern' => 'temporary/(:segment)/(:segment)',
                'handler' => [AssetConnectController::class, 'temporary/$1'],
                'options' => ['priority' => 100, 'as' => 'asset-connect.temporary'],
            ],
            [
                'pattern' => 'temporary/(:segment)/variant/(:segment)/(:segment)',
                'handler' => [AssetConnectController::class, 'temporary/$1'],
                'options' => ['priority' => 100, 'as' => 'asset-connect.temporary_variant'],
            ],
        ];

        // Counter to track which call we're on
        $callIndex = 0;

        $mockGroupRoutes->expects($this->exactly(4))
            ->method('get')
            ->willReturnCallback(function ($pattern, $handler, $options) use (&$callIndex, $expectedCalls, $mockGroupRoutes) {
                $expected = $expectedCalls[$callIndex];
                $this->assertSame($expected['pattern'], $pattern);
                $this->assertSame($expected['handler'], $handler);
                $this->assertSame($expected['options']['priority'], $options['priority']);
                $this->assertSame($expected['options']['as'], $options['as']);
                $callIndex++;

                return $mockGroupRoutes;
            });

        // Call the group callback
        $groupCallback($mockGroupRoutes);
    }

    /**
     * Test params method
     */
    public function testParams(): void
    {
        // Arrange
        $asset = new Asset([
            'id'        => 123,
            'file_name' => 'test.jpg',
        ]);
        $asset->metadata->assetVariant->addAssetVariant(new AssetVariant([
            'name' => 'thumbnail',
            'path' => '/fake/thumbnail.png',
        ]));

        $variant = $asset->metadata->assetVariant->getAssetVariant('thumbnail');
        $token   = 'test_token';

        // Act
        $params = DefaultUrlGenerator::params($asset, $variant, $token);

        // Assert
        $this->assertIsArray($params);
        $this->assertCount(4, $params);
        $this->assertArrayHasKey('asset-connect.show', $params);
        $this->assertArrayHasKey('asset-connect.show_variant', $params);
        $this->assertArrayHasKey('asset-connect.temporary', $params);
        $this->assertArrayHasKey('asset-connect.temporary_variant', $params);

        // Check the params for each route
        $this->assertSame([$asset->id, $asset->file_name], $params['asset-connect.show']);
        $this->assertSame([$asset->id, $variant->name, $variant->file_name], $params['asset-connect.show_variant']);
        $this->assertSame([$token, $asset->file_name], $params['asset-connect.temporary']);
        $this->assertSame([$token, $variant->name, $variant->file_name], $params['asset-connect.temporary_variant']);
    }

    /**
     * Test params method with null variant name
     */
    public function testParamsWithNullVariantName(): void
    {
        // Arrange
        $asset = new Asset([
            'id'        => 123,
            'file_name' => 'test.jpg',
        ]);
        $assetId     = $asset->id;
        $variantName = null;
        $filename    = $asset->file_name;
        $token       = 'test_token';

        // Act
        $params = DefaultUrlGenerator::params($asset, $variantName, $token);

        // Assert
        $this->assertIsArray($params);
        $this->assertCount(4, $params);

        // Check the params for each route
        $this->assertSame([$assetId, $filename], $params['asset-connect.show']);
        $this->assertSame([$assetId, $variantName, $variantName], $params['asset-connect.show_variant']);
        $this->assertSame([$token, $filename], $params['asset-connect.temporary']);
        $this->assertSame([$token, $variantName, $variantName], $params['asset-connect.temporary_variant']);
    }

    /**
     * Test params method with null token
     */
    public function testParamsWithNullToken(): void
    {
        // Arrange
        $asset = new Asset([
            'id'        => 123,
            'file_name' => 'test.jpg',
        ]);
        $asset->metadata->assetVariant->addAssetVariant(new AssetVariant([
            'name' => 'thumbnail',
            'path' => '/fake/thumbnail.png',
        ]));

        $variant  = $asset->metadata->assetVariant->getAssetVariant('thumbnail');
        $assetId  = $asset->id;
        $filename = $asset->file_name;
        $token    = null;

        // Act
        $params = DefaultUrlGenerator::params($asset, $variant, $token);

        // Assert
        $this->assertIsArray($params);
        $this->assertCount(4, $params);

        // Check the params for each route
        $this->assertSame([$assetId, $filename], $params['asset-connect.show']);
        $this->assertSame([$assetId, $variant->name, $variant->file_name], $params['asset-connect.show_variant']);
        $this->assertSame([$token, $filename], $params['asset-connect.temporary']);
        $this->assertSame([$token, $variant->name, $variant->file_name], $params['asset-connect.temporary_variant']);
    }
}
