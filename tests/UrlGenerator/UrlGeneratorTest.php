<?php

declare(strict_types=1);

namespace Tests\UrlGenerator;

use CodeIgniter\Config\Factories;
use CodeIgniter\Config\Services;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Maniaba\AssetConnect\Asset\Asset;
use Maniaba\AssetConnect\Exceptions\InvalidArgumentException;
use Maniaba\AssetConnect\UrlGenerator\UrlGenerator;
use Tests\Support\Config\TestAssetConfig;

/**
 * @internal
 */
final class UrlGeneratorTest extends CIUnitTestCase
{
    private Asset $asset;

    protected function setUp(): void
    {
        parent::setUp();
        // Inject test config after parent::setUp() using the full class name
        Factories::injectMock('config', \Maniaba\AssetConnect\Config\Asset::class, new TestAssetConfig());
        // Create a real Asset object with metadata via constructor
        $this->asset = new Asset([
            'id'         => '123',
            'file_name'  => 'test.jpg',
            'storage'    => 'public',
            'path'       => 'uploads/test.jpg',
            'collection' => 'default_collection',
            'metadata'   => json_encode([
                'asset_variants' => [
                    'thumbnail' => [
                        'name'                  => 'thumbnail',
                        'storage'               => 'public',
                        'path'                  => 'uploads/variants/test_thumbnail.jpg',
                        'relative_path_for_url' => 'uploads/variants/test_thumbnail.jpg',
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
        $this->assertSame('https://example.com/index.php/assets/storage/uploads/test.jpg', $url);
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

    public function testGetUrlForPublicStorageWithoutPublicUrlGeneratorThrowsConfigurationMessage(): void
    {
        $config           = new TestAssetConfig();
        $config->storages = [
            'ftp_public' => [
                'filesystem' => new Filesystem(new LocalFilesystemAdapter(sys_get_temp_dir())),
                'visibility' => 'public',
            ],
        ];

        Factories::injectMock('config', \Maniaba\AssetConnect\Config\Asset::class, $config);

        $asset = new Asset([
            'id'         => '123',
            'file_name'  => 'test.jpg',
            'storage'    => 'ftp_public',
            'path'       => 'uploads/test.jpg',
            'collection' => 'default_collection',
            'metadata'   => json_encode([
                'asset_variants' => [],
            ]),
        ]);

        try {
            UrlGenerator::create($asset)->getUrl();
        } catch (InvalidArgumentException $exception) {
            $this->assertSame(
                ["Public storage disk 'ftp_public' cannot generate asset URLs. Configure public_url for this disk, provide a Flysystem public URL generator, or mark the disk as protected to serve assets through AssetConnect routes."],
                $exception->errors,
            );

            return;
        }

        $this->fail('Expected missing public URL generator exception.');
    }

    public function testGetUrlForProtectedStorageUsesControllerRoute(): void
    {
        $asset = new Asset([
            'id'         => '123',
            'file_name'  => 'test.jpg',
            'storage'    => 'protected',
            'path'       => 'secure/test.jpg',
            'collection' => 'default_collection',
            'metadata'   => json_encode([
                'asset_variants' => [
                    'thumbnail' => [
                        'name'                  => 'thumbnail',
                        'storage'               => 'protected',
                        'path'                  => 'secure/variants/test_thumbnail.jpg',
                        'relative_path_for_url' => 'secure/variants/test_thumbnail.jpg',
                    ],
                ],
            ]),
        ]);

        $urlGenerator = UrlGenerator::create($asset);

        $url = $urlGenerator->getUrl();

        $this->assertSame('https://example.com/index.php/assets/123/test.jpg', $url);
    }

    public function testGetUrlForProtectedStorageVariantUsesControllerRoute(): void
    {
        $asset = new Asset([
            'id'         => '123',
            'file_name'  => 'test.jpg',
            'storage'    => 'protected',
            'path'       => 'secure/test.jpg',
            'collection' => 'default_collection',
            'metadata'   => json_encode([
                'asset_variants' => [
                    'thumbnail' => [
                        'name'                  => 'thumbnail',
                        'file_name'             => 'test_thumbnail.jpg',
                        'storage'               => 'protected',
                        'path'                  => 'secure/variants/test_thumbnail.jpg',
                        'relative_path_for_url' => 'secure/variants/test_thumbnail.jpg',
                    ],
                ],
            ]),
        ]);

        $urlGenerator = UrlGenerator::create($asset);

        $url = $urlGenerator->getUrl('thumbnail');

        $this->assertSame('https://example.com/index.php/assets/123/variant/thumbnail/test_thumbnail.jpg', $url);
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
        $this->assertSame('https://example.com/index.php/assets/temporary/b0a4ae59595b37c409e6196189b3f22854f578e66a1fe526cee293792c8b166c/variant/thumbnail/test_thumbnail.jpg', $url);
    }

    public function testAssetTraitTemporaryUrlSupportsForceDownload(): void
    {
        Time::setTestNow('2025-10-01 12:00:00');

        $expiration = Time::now()->addHours(1);

        $this->assertSame(
            'https://example.com/index.php/assets/temporary/b0a4ae59595b37c409e6196189b3f22854f578e66a1fe526cee293792c8b166c/variant/thumbnail/test_thumbnail.jpg?download=force',
            $this->asset->getTemporaryUrl($expiration, 'thumbnail', true),
            'Asset temporary URLs should append the force-download query when requested.',
        );
    }

    public function testAssetTraitTemporaryRelativeUrlKeepsPathAndQuery(): void
    {
        Time::setTestNow('2025-10-01 12:00:00');

        $expiration = Time::now()->addHours(1);

        $this->assertSame(
            '/index.php/assets/temporary/b0a4ae59595b37c409e6196189b3f22854f578e66a1fe526cee293792c8b166c/variant/thumbnail/test_thumbnail.jpg?download=force',
            $this->asset->getTemporaryUrlRelative($expiration, 'thumbnail', true),
            'Relative temporary URLs should strip scheme and host while keeping the query string.',
        );
    }

    public function testAssetTraitRelativeUrlReturnsOriginalUrlWhenNoPathCanBeParsed(): void
    {
        $toRelativeUrl = $this->getPrivateMethodInvoker($this->asset, 'toRelativeUrl');

        $this->assertSame(
            'https://example.com',
            $toRelativeUrl('https://example.com'),
            'Relative URL conversion should return the original URL when it has no path component.',
        );
    }

    /**
     * Test routeTo method
     */
    public function testRouteTo(): void
    {
        // Arrange
        $routeName = 'asset-connect.show';

        // Build an Asset instance (instead of passing raw id/filename)
        $asset = new Asset([
            'id'        => 123,
            'file_name' => 'test.jpg',
        ]);

        // Mock is_subclass_of
        global $mockFunctions;
        $mockFunctions['is_subclass_of'] = static fn ($class, $interface) => true;

        // Mock the DefaultUrlGenerator::params method to accept new signature
        // Expected signature now: params(Asset $asset, ?object $variant = null, ?string $token = null)
        $mockFunctions['Maniaba\AssetConnect\UrlGenerator\DefaultUrlGenerator::params'] = function ($passedAsset, $variant, $token) {
            $this->assertInstanceOf(Asset::class, $passedAsset);
            $this->assertSame(123, $passedAsset->id);
            $this->assertSame('test.jpg', $passedAsset->file_name);
            $this->assertNull($variant);
            $this->assertNull($token);

            // Return route params that router expects (scalars), derived from the Asset
            return [
                'asset-connect.show'              => [$passedAsset->id, $passedAsset->file_name],
                'asset-connect.show_variant'      => [$passedAsset->id, null, $passedAsset->file_name],
                'asset-connect.temporary'         => [$token, $passedAsset->file_name],
                'asset-connect.temporary_variant' => [$token, null, $passedAsset->file_name],
            ];
        };

        // Act
        $url = UrlGenerator::routeTo($routeName, $asset, null);

        // Assert
        $this->assertSame('/assets/123/test.jpg', $url);
    }

    /**
     * Test routeTo method with no default URL generator
     */
    public function testRouteToWithNoDefaultUrlGenerator(): void
    {
        // Arrange
        $routeName = 'asset-connect.show';
        $asset     = new Asset([
            'id'        => 123,
            'file_name' => 'test.jpg',
        ]);

        $assetConfig = new class () extends \Maniaba\AssetConnect\Config\Asset {
            public ?string $defaultUrlGenerator = null;
        };

        Factories::injectMock('config', 'Asset', $assetConfig);

        // Act
        $url = UrlGenerator::routeTo($routeName, $asset, null);

        // Assert
        $this->assertSame('', $url);
    }

    /**
     * Test routeTo method with undefined route
     */
    public function testRouteToWithUndefinedRoute(): void
    {
        // Arrange
        $routeName = 'undefined-route';
        $asset     = new Asset([
            'id'        => 123,
            'file_name' => 'test.jpg',
        ]);

        // Mock is_subclass_of
        global $mockFunctions;
        $mockFunctions['is_subclass_of'] = static fn ($class, $interface) => true;

        // Mock the DefaultUrlGenerator::params method for new signature
        $mockFunctions['Maniaba\AssetConnect\UrlGenerator\DefaultUrlGenerator::params'] = static fn ($passedAsset, $variant, $token) => [
            'asset-connect.show'              => [$passedAsset->id, $passedAsset->file_name],
            'asset-connect.show_variant'      => [$passedAsset->id, null, $passedAsset->file_name],
            'asset-connect.temporary'         => [$token, $passedAsset->file_name],
            'asset-connect.temporary_variant' => [$token, null, $passedAsset->file_name],
        ];

        // Act & Assert
        $this->expectException(InvalidArgumentException::class);
        UrlGenerator::routeTo($routeName, $asset, null);
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
