<?php

declare(strict_types=1);

namespace Tests\UrlGenerator;

use CodeIgniter\Router\RouteCollection;
use CodeIgniter\Test\CIUnitTestCase;
use Maniaba\FileConnect\Controllers\AssetConnectController;
use Maniaba\FileConnect\UrlGenerator\DefaultUrlGenerator;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @internal
 */
final class DefaultUrlGeneratorTest extends CIUnitTestCase
{
    private RouteCollection|MockObject $mockRoutes;

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
                $this->equalTo('assets'),
                $this->callback(function ($callback) use (&$groupCallback) {
                    $groupCallback = $callback;
                    return true;
                })
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
                'options' => ['priority' => 100, 'as' => 'asset-connect.show']
            ],
            [
                'pattern' => '(:num)/variant/(:segment)/(:segment)',
                'handler' => [AssetConnectController::class, 'show/$1/$2'],
                'options' => ['priority' => 100, 'as' => 'asset-connect.show_variant']
            ],
            [
                'pattern' => 'temporary/(:segment)/(:segment)',
                'handler' => [AssetConnectController::class, 'temporary/$1'],
                'options' => ['priority' => 100, 'as' => 'asset-connect.temporary']
            ],
            [
                'pattern' => 'temporary/(:segment)/variant/(:segment)/(:segment)',
                'handler' => [AssetConnectController::class, 'temporary/$1'],
                'options' => ['priority' => 100, 'as' => 'asset-connect.temporary_variant']
            ]
        ];

        // Counter to track which call we're on
        $callIndex = 0;

        $mockGroupRoutes->expects($this->exactly(4))
            ->method('get')
            ->willReturnCallback(function ($pattern, $handler, $options) use (&$callIndex, $expectedCalls, $mockGroupRoutes) {
                $expected = $expectedCalls[$callIndex];
                $this->assertEquals($expected['pattern'], $pattern);
                $this->assertEquals($expected['handler'], $handler);
                $this->assertEquals($expected['options']['priority'], $options['priority']);
                $this->assertEquals($expected['options']['as'], $options['as']);
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
        $assetId = 123;
        $variantName = 'thumbnail';
        $filename = 'test.jpg';
        $token = 'test_token';

        // Act
        $params = DefaultUrlGenerator::params($assetId, $variantName, $filename, $token);

        // Assert
        $this->assertIsArray($params);
        $this->assertCount(4, $params);
        $this->assertArrayHasKey('asset-connect.show', $params);
        $this->assertArrayHasKey('asset-connect.show_variant', $params);
        $this->assertArrayHasKey('asset-connect.temporary', $params);
        $this->assertArrayHasKey('asset-connect.temporary_variant', $params);

        // Check the params for each route
        $this->assertSame([$assetId, $filename], $params['asset-connect.show']);
        $this->assertSame([$assetId, $variantName, $filename], $params['asset-connect.show_variant']);
        $this->assertSame([$token, $filename], $params['asset-connect.temporary']);
        $this->assertSame([$token, $variantName, $filename], $params['asset-connect.temporary_variant']);
    }

    /**
     * Test params method with null variant name
     */
    public function testParamsWithNullVariantName(): void
    {
        // Arrange
        $assetId = 123;
        $variantName = null;
        $filename = 'test.jpg';
        $token = 'test_token';

        // Act
        $params = DefaultUrlGenerator::params($assetId, $variantName, $filename, $token);

        // Assert
        $this->assertIsArray($params);
        $this->assertCount(4, $params);

        // Check the params for each route
        $this->assertSame([$assetId, $filename], $params['asset-connect.show']);
        $this->assertSame([$assetId, $variantName, $filename], $params['asset-connect.show_variant']);
        $this->assertSame([$token, $filename], $params['asset-connect.temporary']);
        $this->assertSame([$token, $variantName, $filename], $params['asset-connect.temporary_variant']);
    }

    /**
     * Test params method with null token
     */
    public function testParamsWithNullToken(): void
    {
        // Arrange
        $assetId = 123;
        $variantName = 'thumbnail';
        $filename = 'test.jpg';
        $token = null;

        // Act
        $params = DefaultUrlGenerator::params($assetId, $variantName, $filename, $token);

        // Assert
        $this->assertIsArray($params);
        $this->assertCount(4, $params);

        // Check the params for each route
        $this->assertSame([$assetId, $filename], $params['asset-connect.show']);
        $this->assertSame([$assetId, $variantName, $filename], $params['asset-connect.show_variant']);
        $this->assertSame([$token, $filename], $params['asset-connect.temporary']);
        $this->assertSame([$token, $variantName, $filename], $params['asset-connect.temporary_variant']);
    }
}
