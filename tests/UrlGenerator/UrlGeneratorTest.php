<?php

declare(strict_types=1);

namespace Tests\UrlGenerator;

use CodeIgniter\Config\Factories;
use CodeIgniter\Config\Services;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use Maniaba\FileConnect\Asset\Asset;
use Maniaba\FileConnect\Exceptions\InvalidArgumentException;
use Maniaba\FileConnect\UrlGenerator\UrlGenerator;
use stdClass;

/**
 * @internal
 */
final class UrlGeneratorTest extends CIUnitTestCase
{
    private Asset $asset;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a real Asset object with metadata via constructor
        $this->asset = new Asset([
            'id'        => '123',
            'file_name' => 'test.jpg',
            'path'      => '/path/to/test.jpg',
            'metadata'  => json_encode([
                'basic_info' => [
                    'file_relative_path' => 'uploads',
                    'collection_class'   => null, // Not a protected collection
                ],
                'asset_variants' => [
                    'thumbnail' => [
                        'name'                  => 'thumbnail',
                        'relative_path_for_url' => 'uploads/variants/test_thumbnail.jpg',
                        'paths'                 => [
                            'storage_base_directory_path' => '/path/to',
                            'file_relative_path'          => 'uploads/variants',
                        ],
                    ],
                ],
            ]),
        ]);

        // Mock global functions
        $this->setupGlobalFunctionMocks();

        // Mock the Factories class to return a mock config
        Services::reset();

        // routes load to ensure routes are available
        Services::routes()->loadRoutes();
    }

    /**
     * Setup global function mocks
     */
    private function setupGlobalFunctionMocks(): void
    {
        global $mockFunctions;

        // Mock site_url function
        $mockFunctions['site_url'] = static fn ($path) => 'http://example.com/' . $path;

        // Mock route_to function
        $mockFunctions['route_to'] = static function ($name, ...$params) {
            if ($name === 'asset-connect.show') {
                return 'assets/' . $params[0] . '/' . $params[1];
            }
            if ($name === 'asset-connect.show_variant') {
                return 'assets/' . $params[0] . '/variant/' . $params[1] . '/' . $params[2];
            }
            if ($name === 'asset-connect.temporary') {
                return 'assets/temporary/' . $params[0] . '/' . $params[1];
            }
            if ($name === 'asset-connect.temporary_variant') {
                return 'assets/temporary/' . $params[0] . '/variant/' . $params[1] . '/' . $params[2];
            }

            return false;
        };

        // Mock config function
        $mockFunctions['config'] = static function ($name) {
            if ($name === 'Asset') {
                $config                      = new stdClass();
                $config->defaultUrlGenerator = 'Maniaba\FileConnect\UrlGenerator\DefaultUrlGenerator';

                return $config;
            }

            return null;
        };
    }

    /**
     * Test getUrl method for non-protected collection without variant
     */
    public function testGetUrlForNonProtectedCollectionWithoutVariant(): void
    {
        // Arrange
        // Create the URL generator
        $urlGenerator = UrlGenerator::create($this->asset);

        // Act
        $url = $urlGenerator->getUrl();

        // Assert
        $this->assertSame('https://example.com/index.php/uploads/test.jpg', $url);
    }

    /**
     * Test getUrl method for non-protected collection with non-existent variant
     */
    public function testGetUrlForNonProtectedCollectionWithNonExistentVariant(): void
    {
        // Arrange
        // Create the URL generator
        $urlGenerator = UrlGenerator::create($this->asset);

        // Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $urlGenerator->getUrl('non_existent');
    }

    /**
     * Test getUrl method for protected collection without variant
     */
    public function testGetUrlForProtectedCollectionWithoutVariant(): void
    {
        // Arrange
        // Create a new Asset object with a protected collection
        $asset = new Asset([
            'id'        => '123',
            'file_name' => 'test.jpg',
            'path'      => '/path/to/test.jpg',
            'metadata'  => json_encode([
                'basic_info' => [
                    'file_relative_path' => 'uploads',
                    'collection_class'   => 'Maniaba\FileConnect\Asset\Interfaces\AuthorizableAssetCollectionDefinitionInterface', // Protected collection
                ],
                'asset_variants' => [
                    'thumbnail' => [
                        'name'                  => 'thumbnail',
                        'relative_path_for_url' => 'uploads/variants/test_thumbnail.jpg',
                    ],
                ],
            ]),
        ]);

        // Create the URL generator
        $urlGenerator = UrlGenerator::create($asset);

        // Act
        $url = $urlGenerator->getUrl();

        // Assert
        $this->assertSame('https://example.com/index.php/uploads/test.jpg', $url);
    }

    /**
     * Test getTemporaryUrl method with variant
     */
    public function testGetTemporaryUrlWithVariant(): void
    {
        // freeze Time to ensure get same hash
        Time::setTestNow('2025-10-01 12:00:00');

        // Arrange
        $expiration  = Time::now()->addHours(1);
        $variantName = 'thumbnail';

        // Create the URL generator
        $urlGenerator = UrlGenerator::create($this->asset);

        // Act
        $url = $urlGenerator->getTemporaryUrl($expiration, $variantName);

        // Assert
        $this->assertSame('/assets/temporary/b0a4ae59595b37c409e6196189b3f22854f578e66a1fe526cee293792c8b166c/variant/thumbnail/test.jpg', $url);
    }

    /**
     * Test routeTo method
     */
    public function testRouteTo(): void
    {
        // Arrange
        $routeName   = 'asset-connect.show';
        $assetId     = 123;
        $variantName = null;
        $filename    = 'test.jpg';

        // Mock is_subclass_of
        global $mockFunctions;
        $mockFunctions['is_subclass_of'] = static fn ($class, $interface) => true;

        // Mock the DefaultUrlGenerator::params method
        $mockFunctions['Maniaba\FileConnect\UrlGenerator\DefaultUrlGenerator::params'] = function ($id, $variant, $file, $token) use ($assetId, $variantName, $filename) {
            $this->assertSame($assetId, $id);
            $this->assertSame($variantName, $variant);
            $this->assertSame($filename, $file);
            $this->assertNull($token);

            return [
                'asset-connect.show'              => [$id, $file],
                'asset-connect.show_variant'      => [$id, $variant, $file],
                'asset-connect.temporary'         => [$token, $file],
                'asset-connect.temporary_variant' => [$token, $variant, $file],
            ];
        };

        // Act
        $url = UrlGenerator::routeTo($routeName, $assetId, $variantName, $filename);

        // Assert
        $this->assertSame('/assets/123/test.jpg', $url);
    }

    /**
     * Test routeTo method with no default URL generator
     */
    public function testRouteToWithNoDefaultUrlGenerator(): void
    {
        // Arrange
        $routeName   = 'asset-connect.show';
        $assetId     = 123;
        $variantName = null;
        $filename    = 'test.jpg';

        $assetConfig = new class () extends \Maniaba\FileConnect\Config\Asset {
            public ?string $defaultUrlGenerator = null;
        };

        Factories::injectMock('config', 'Asset', $assetConfig);

        // Act
        $url = UrlGenerator::routeTo($routeName, $assetId, $variantName, $filename);

        // Assert
        $this->assertSame('', $url);
    }

    /**
     * Test routeTo method with undefined route
     */
    public function testRouteToWithUndefinedRoute(): void
    {
        // Arrange
        $routeName   = 'undefined-route';
        $assetId     = 123;
        $variantName = null;
        $filename    = 'test.jpg';

        // Mock is_subclass_of
        global $mockFunctions;
        $mockFunctions['is_subclass_of'] = static fn ($class, $interface) => true;

        // Mock the DefaultUrlGenerator::params method
        $mockFunctions['Maniaba\FileConnect\UrlGenerator\DefaultUrlGenerator::params'] = static fn ($id, $variant, $file, $token) => [
            'asset-connect.show'              => [$id, $file],
            'asset-connect.show_variant'      => [$id, $variant, $file],
            'asset-connect.temporary'         => [$token, $file],
            'asset-connect.temporary_variant' => [$token, $variant, $file],
        ];

        // Act & Assert
        $this->expectException(InvalidArgumentException::class);
        UrlGenerator::routeTo($routeName, $assetId, $variantName, $filename);
    }

    /**
     * Test create method
     */
    public function testCreate(): void
    {
        // Act
        $urlGenerator = UrlGenerator::create($this->asset);

        // Assert
        $this->assertInstanceOf(UrlGenerator::class, $urlGenerator);
    }
}
