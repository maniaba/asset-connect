<?php

declare(strict_types=1);

namespace Tests\Models;

use CodeIgniter\Config\Factories;
use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Test\CIUnitTestCase;
use Exception;
use Maniaba\AssetConnect\Config\Asset as AssetConfig;
use Maniaba\AssetConnect\Models\AssetModel;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Stub;
use RuntimeException;
use stdClass;
use Tests\Support\AssetCollections\ProtectedTestAssetCollection;
use Tests\Support\Config\TestAssetConfig;
use Tests\Support\Entities\UnregisteredAssetEntity;
use Tests\Support\Models\ConfiguredBaseModel;
use Tests\Support\Models\RecordingPlatformAssetModel;

/**
 * @internal
 */
final class AssetModelTest extends CIUnitTestCase
{
    private AssetConfig&Stub $mockAssetConfig;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockAssetConfig = $this->createStub(AssetConfig::class);
        // Setup global function mocks
        $this->setupGlobalFunctionMocks();
    }

    /**
     * Setup global function mocks
     */
    private function setupGlobalFunctionMocks(): void
    {
        // Inject mock for config
        Factories::injectMock('config', AssetConfig::class, $this->mockAssetConfig);
        Factories::injectMock('config', 'Asset', $this->mockAssetConfig);

        // Inject mock for AssetModel - used by testInitSuccessful
        $assetModel = new AssetModel($this->stubConnection());
        Factories::injectMock('models', AssetModel::class, $assetModel);
    }

    /**
     * Test successful initialization of AssetModel
     */
    public function testInitSuccessful(): void
    {
        // Arrange
        $this->mockAssetConfig->assetModel = AssetModel::class;

        // Act
        $result = AssetModel::init(true, $this->stubConnection());

        // @phpstan-ignore-next-line No throws expected
        $this->assertInstanceOf(AssetModel::class, $result);
    }

    /**
     * Test initialization with invalid model class
     */
    public function testInitWithInvalidModelClass(): void
    {
        // @phpstan-ignore-next-line
        $this->mockAssetConfig->assetModel = stdClass::class;

        // Act & Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Asset model class must extend ' . AssetModel::class);
        AssetModel::init(true, $this->stubConnection());
    }

    /**
     * Test initialization with invalid model instance
     */
    public function testInitWithInvalidModelInstance(): void
    {
        // Override the model mock to return an invalid instance
        $invalidInstance = new stdClass();
        Factories::injectMock('models', AssetModel::class, $invalidInstance);

        // Act & Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Asset model must be an instance of ' . AssetModel::class . ' or a subclass of it');
        AssetModel::init(true, $this->stubConnection());
    }

    /**
     * Test initialization with invalid return type
     */
    public function testInitWithInvalidReturnType(): void
    {
        // Create a valid AssetModel instance but with an invalid return type
        $invalidReturnTypeModel = model(AssetModel::class, false);
        $this->setPrivateProperty($invalidReturnTypeModel, 'returnType', stdClass::class);

        Factories::injectMock('models', AssetModel::class, $invalidReturnTypeModel);

        // Act & Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Asset model return type must be Asset or a subclass of Asset');
        AssetModel::init(true, $this->stubConnection());
    }

    public function testStorageValidationRuleMatchesDatabaseConstraint(): void
    {
        $model = new AssetModel($this->stubConnection());

        /** @var array<string, string> $validationRules */
        $validationRules = $this->getPrivateProperty($model, 'validationRules');

        $this->assertArrayHasKey('storage', $validationRules);
        $this->assertSame('required|alpha_dash|max_length[20]', $validationRules['storage']);
    }

    #[DataProvider('provideFilterByPropertyBuildsPlatformSpecificWhereClause')]
    public function testFilterByPropertyBuildsPlatformSpecificWhereClause(string $platform, string $expectedKey, string $expectedValue): void
    {
        $model = $this->recordingModel($platform);

        $result = $model->filterByProperty('profile.name', 'Amel', '!=');

        $this->assertSame($model, $result, 'filterByProperty should remain chainable.');
        $this->assertSame(
            [[$expectedKey, $expectedValue, null]],
            $model->whereCalls,
            "filterByProperty should build the {$platform} JSON where clause.",
        );
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function provideFilterByPropertyBuildsPlatformSpecificWhereClause(): iterable
    {
        yield 'mysql' => [
            'MySQLi',
            "JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.user_custom.profile.name')) !=",
            'Amel',
        ];

        yield 'postgres' => [
            'Postgre',
            "metadata#>>'{user_custom,profile,name}' !=",
            'Amel',
        ];

        yield 'sqlsrv' => [
            'SQLSRV',
            "JSON_VALUE(metadata, '$.user_custom.profile.name') !=",
            'Amel',
        ];

        yield 'fallback' => [
            'OCI8',
            'metadata LIKE',
            '%"user_custom"%"profile.name":"Amel"%',
        ];
    }

    #[DataProvider('provideFilterByPropertyExistsBuildsPlatformSpecificWhereClause')]
    public function testFilterByPropertyExistsBuildsPlatformSpecificWhereClause(string $platform, string $expectedKey, ?string $expectedValue): void
    {
        $model = $this->recordingModel($platform);

        $result = $model->filterByPropertyExists('profile.name');

        $this->assertSame($model, $result, 'filterByPropertyExists should remain chainable.');
        $this->assertSame(
            [[$expectedKey, $expectedValue, null]],
            $model->whereCalls,
            "filterByPropertyExists should build the {$platform} JSON existence clause.",
        );
    }

    /**
     * @return iterable<string, array{string, string, string|null}>
     */
    public static function provideFilterByPropertyExistsBuildsPlatformSpecificWhereClause(): iterable
    {
        yield 'mysql' => [
            'MySQLi',
            "JSON_CONTAINS_PATH(metadata, 'one', '$.user_custom.profile.name') = 1",
            null,
        ];

        yield 'postgres' => [
            'Postgre',
            "metadata#>'{user_custom,profile,name}' IS NOT NULL",
            null,
        ];

        yield 'sqlsrv' => [
            'SQLSRV',
            "JSON_VALUE(metadata, '$.user_custom.profile.name') IS NOT NULL",
            null,
        ];

        yield 'fallback' => [
            'OCI8',
            'metadata LIKE',
            '%"user_custom"%"profile.name"%',
        ];
    }

    #[DataProvider('provideFilterByPropertyContainsBuildsPlatformSpecificWhereClause')]
    public function testFilterByPropertyContainsBuildsPlatformSpecificWhereClause(string $platform, string $expectedKey, ?string $expectedValue): void
    {
        $model = $this->recordingModel($platform);

        $result = $model->filterByPropertyContains('tags', 'public');

        $this->assertSame($model, $result, 'filterByPropertyContains should remain chainable.');
        $this->assertSame(
            [[$expectedKey, $expectedValue, null]],
            $model->whereCalls,
            "filterByPropertyContains should build the {$platform} JSON contains clause.",
        );
    }

    /**
     * @return iterable<string, array{string, string, string|null}>
     */
    public static function provideFilterByPropertyContainsBuildsPlatformSpecificWhereClause(): iterable
    {
        yield 'mysql' => [
            'MySQLi',
            "JSON_CONTAINS(JSON_EXTRACT(metadata, '$.user_custom.tags'), '\"public\"')",
            null,
        ];

        yield 'postgres' => [
            'Postgre',
            "metadata#>'{user_custom,tags}' @> '\"public\"'::jsonb",
            null,
        ];

        yield 'sqlsrv' => [
            'SQLSRV',
            "JSON_QUERY(metadata, '$.user_custom.tags') LIKE",
            '%public%',
        ];

        yield 'fallback' => [
            'OCI8',
            'metadata LIKE',
            '%"user_custom"%"tags"%public%',
        ];
    }

    public function testWhereCollectionFallsBackToNullForUnknownCollection(): void
    {
        $this->injectAssetConfig(new TestAssetConfig());
        $model = $this->recordingModel();

        $result = $model->whereCollection(ProtectedTestAssetCollection::class);

        $this->assertSame($model, $result, 'whereCollection should remain chainable for unknown collections.');
        $this->assertSame(
            [['collection', null, null]],
            $model->whereCalls,
            'whereCollection should constrain collection to null when the collection key cannot be resolved.',
        );
    }

    public function testWhereEntityTypeFallsBackToNullForUnknownEntityType(): void
    {
        $this->injectAssetConfig(new TestAssetConfig());
        $model = $this->recordingModel();

        $result = $model->whereEntityType(UnregisteredAssetEntity::class);

        $this->assertSame($model, $result, 'whereEntityType should remain chainable for unknown entity types.');
        $this->assertSame(
            [['entity_type', null, null]],
            $model->whereCalls,
            'whereEntityType should constrain entity_type to null when the entity key cannot be resolved.',
        );
    }

    public function testBaseModelUsesConfiguredTableAndDatabaseGroup(): void
    {
        $config                   = new TestAssetConfig();
        $config->DBGroup          = 'tests';
        $config->tables['assets'] = 'custom_assets';
        $this->injectAssetConfig($config);

        $model = new ConfiguredBaseModel($this->stubConnection());

        $this->assertSame('custom_assets', $model->getTableName(), 'BaseModel should use the table name from Asset config.');
        $this->assertSame('tests', $model->configuredDBGroup(), 'BaseModel should copy the configured database group.');
    }

    public function testBaseModelThrowsWhenConfiguredTableIsMissing(): void
    {
        $config         = new TestAssetConfig();
        $config->tables = [];
        $this->injectAssetConfig($config);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Table not found in Asset config');

        new ConfiguredBaseModel($this->stubConnection());
    }

    private function injectAssetConfig(AssetConfig $assetConfig): void
    {
        Factories::injectMock('config', AssetConfig::class, $assetConfig);
        Factories::injectMock('config', 'Asset', $assetConfig);
    }

    private function recordingModel(string $platform = 'SQLite3'): RecordingPlatformAssetModel
    {
        return (new RecordingPlatformAssetModel($this->stubConnection()))->useDatabasePlatform($platform);
    }

    private function &stubConnection(string $platform = 'SQLite3'): ConnectionInterface
    {
        $db = $this->createStub(ConnectionInterface::class);
        $db->method('getPlatform')->willReturn($platform);
        $db->method('escape')->willReturnCallback(self::escapeDatabaseValue(...));

        return $db;
    }

    /**
     * @param array<array-key, bool|float|int|object|string|null>|bool|float|int|object|string|null $value
     *
     * @return array<array-key, array<array-key, bool|float|int|string>|float|int|string>|float|int|string
     */
    private static function escapeDatabaseValue(array|bool|float|int|object|string|null $value): array|float|int|string
    {
        if (is_array($value)) {
            return array_map(self::escapeDatabaseValue(...), $value);
        }

        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        if ($value === null) {
            return 'NULL';
        }

        if (is_float($value) || is_int($value)) {
            return $value;
        }

        if (is_object($value)) {
            $value = $value::class;
        }

        return "'" . str_replace("'", "''", $value) . "'";
    }
}
