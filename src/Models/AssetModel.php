<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Models;

use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Entity\Entity;
use CodeIgniter\Validation\ValidationInterface;
use Maniaba\AssetConnect\Asset\Asset;
use Maniaba\AssetConnect\Asset\Interfaces\AssetCollectionDefinitionInterface;
use Maniaba\AssetConnect\AssetCollection\AssetCollectionDefinitionFactory;
use Maniaba\AssetConnect\Traits\UseAssetConnectTrait;
use Override;
use RuntimeException;

/**
 * @method Asset|list<Asset>|null find($id = null)
 * @method list<Asset>            findAll(int $limit = 0, int $offset = 0)
 * @method Asset|null             first()
 *
 * @property-read string              $createdField
 * @property-read string              $deletedField
 * @property-read string              $primaryKey
 * @property-read class-string<Asset> $returnType
 * @property-read string              $updatedField
 * @property-read bool                $useSoftDeletes
 * @property-read bool                $useTimestamps
 */
class AssetModel extends BaseModel
{
    protected $allowedFields = [
        'entity_type', 'entity_id', 'collection', 'name', 'file_name', 'mime_type', 'size', 'path', 'order', 'metadata', 'created_at', 'updated_at', 'deleted_at',
    ];
    protected $useSoftDeletes  = true;
    protected $useTimestamps   = true;
    protected $dateFormat      = 'datetime';
    protected $createdField    = 'created_at';
    protected $updatedField    = 'updated_at';
    protected $deletedField    = 'deleted_at';
    protected $returnType      = Asset::class;
    protected $validationRules = [
        'entity_type' => 'required|alpha_numeric_space|max_length[32]',
        'entity_id'   => 'required|integer',
        'collection'  => 'required|alpha_numeric_space|max_length[32]',
        'name'        => 'permit_empty|max_length[255]',
        'file_name'   => 'permit_empty|max_length[255]',
        'mime_type'   => 'permit_empty|max_length[255]',
        'size'        => 'required|integer',
        'path'        => 'required|max_length[1020]',
        'order'       => 'permit_empty|integer',
        'metadata'    => 'permit_empty|valid_json|max_length[65535]',
    ];

    /**
     * Constructor, we make it final to prevent overriding the constructor
     * and ensure that the model is always initialized with the correct database connection and validation instance in AssetModel::init() method.
     *
     * @param ConnectionInterface|null $db         Database connection instance
     * @param ValidationInterface|null $validation Validation instance
     */
    final public function __construct(?ConnectionInterface $db = null, ?ValidationInterface $validation = null)
    {
        parent::__construct($db, $validation);
    }

    #[Override]
    protected function setConfigTableName(): string
    {
        return 'assets';
    }

    /**
     * Filter assets by name
     *
     * @param string $name The name to filter by
     *
     * @return $this
     */
    public function filterByName(string $name): self
    {
        return $this->where('name', $name);
    }

    /**
     * Filter assets by file name
     *
     * @param string $fileName The file name to filter by
     *
     * @return $this
     */
    public function filterByFileName(string $fileName): self
    {
        return $this->where('file_name', $fileName);
    }

    /**
     * Filter assets by MIME type
     *
     * @param string $mimeType The MIME type to filter by
     *
     * @return $this
     */
    public function filterByMimeType(string $mimeType): self
    {
        return $this->where('mime_type', $mimeType);
    }

    /**
     * Filter assets by size
     *
     * @param int    $size     The size to filter by
     * @param string $operator Comparison operator (=, >, <, >=, <=)
     *
     * @return $this
     */
    public function filterBySize(int $size, string $operator = '='): self
    {
        return $this->where('size ' . $operator, $size);
    }

    /**
     * Filter assets by path
     *
     * @param string $path The path to filter by
     *
     * @return $this
     */
    public function filterByPath(string $path): self
    {
        return $this->where('path', $path);
    }

    /**
     * Filter assets by order
     *
     * @param int $order The order to filter by
     *
     * @return $this
     */
    public function filterByOrder(int $order): self
    {
        return $this->where('order', $order);
    }

    /**
     * Get the current database platform/driver
     *
     * CodeIgniter 4 supports multiple database drivers:
     * - MySQLi
     * - Postgre
     * - SQLite3
     * - SQLSRV
     * - OCI8 (Oracle)
     *
     * @return string The database platform/driver name
     */
    protected function getDatabasePlatform(): string
    {
        return $this->db->getPlatform();
    }

    /**
     * Filter assets by properties (JSON column)
     *
     * This method adapts to different database drivers:
     * - MySQLi: Uses JSON_EXTRACT function
     * - Postgre: Uses the -> operator for JSON path navigation
     * - SQLite3: Uses json_extract function
     * - SQLSRV: Uses JSON_VALUE function
     * - Others: Falls back to LIKE comparison (less efficient)
     *
     * @param string $key      The JSON key to filter by (can use dot notation for nested properties)
     * @param mixed  $value    The value to filter by
     * @param string $operator Comparison operator (=, >, <, >=, <=, LIKE, etc.)
     *
     * @return $this
     */
    public function filterByProperty(string $key, $value, string $operator = '='): self
    {
        $platform = $this->getDatabasePlatform();

        // For nested properties, we need to construct the proper JSON path
        // Convert dot notation (e.g., 'user.name') to JSON path ($.user.name)
        $jsonPath = '$.' . $key;

        switch ($platform) {
            case 'MySQLi':
                // MySQL syntax
                return $this->where("JSON_EXTRACT(properties, '{$jsonPath}') {$operator}", $value);

            case 'Postgre':
                // PostgreSQL syntax
                $path = str_replace('.', '->', $key);

                return $this->where("properties->'{$path}' {$operator}", $value);

            case 'SQLite3':
                // SQLite syntax
                return $this->where("json_extract(properties, '{$jsonPath}') {$operator}", $value);

            case 'SQLSRV':
                // SQL Server syntax
                $path = '$.' . $key;

                return $this->where("JSON_VALUE(properties, '{$path}') {$operator}", $value);

            default:
                // Fallback to string-based comparison (less efficient but more compatible)
                return $this->where('properties LIKE ?', '%"' . $key . '":' . json_encode($value) . '%');
        }
    }

    /**
     * Filter assets by checking if a JSON property exists
     *
     * This method adapts to different database drivers:
     * - MySQLi: Uses JSON_CONTAINS_PATH function
     * - Postgre: Uses the ? operator to check for key existence
     * - SQLite3: Checks if json_extract result IS NOT NULL
     * - SQLSRV: Checks if JSON_VALUE result IS NOT NULL
     * - Others: Falls back to LIKE comparison (less efficient)
     *
     * @param string $key The JSON key to check for existence (can use dot notation for nested properties)
     *
     * @return $this
     */
    public function filterByPropertyExists(string $key): self
    {
        $platform = $this->getDatabasePlatform();

        // For nested properties, we need to construct the proper JSON path
        // Convert dot notation (e.g., 'user.name') to JSON path ($.user.name)
        $jsonPath = '$.' . $key;

        switch ($platform) {
            case 'MySQLi':
                // MySQL syntax
                return $this->where("JSON_CONTAINS_PATH(properties, 'one', '{$jsonPath}') = 1");

            case 'Postgre':
                // PostgreSQL syntax
                $path = str_replace('.', '->', $key);

                return $this->where("properties ? '{$path}'");

            case 'SQLite3':
                // SQLite syntax
                return $this->where("json_extract(properties, '{$jsonPath}') IS NOT NULL");

            case 'SQLSRV':
                // SQL Server syntax
                $path = '$.' . $key;

                return $this->where("JSON_VALUE(properties, '{$path}') IS NOT NULL");

            default:
                // Fallback to string-based comparison (less efficient but more compatible)
                return $this->where('properties LIKE ?', '%"' . $key . '"%');
        }
    }

    /**
     * Filter assets by JSON array containing a specific value
     *
     * This method adapts to different database drivers:
     * - MySQLi: Uses JSON_CONTAINS with JSON_EXTRACT
     * - Postgre: Uses the @> operator for JSON containment
     * - SQLite3: Limited support, falls back to LIKE comparison
     * - SQLSRV: Uses JSON_QUERY with LIKE comparison
     * - Others: Falls back to LIKE comparison (less efficient)
     *
     * @param string $arrayKey The JSON array key (can use dot notation for nested arrays)
     * @param mixed  $value    The value to check for in the array
     *
     * @return $this
     */
    public function filterByPropertyContains(string $arrayKey, $value): self
    {
        $platform     = $this->getDatabasePlatform();
        $encodedValue = json_encode($value);

        // For nested properties, we need to construct the proper JSON path
        // Convert dot notation (e.g., 'user.tags') to JSON path ($.user.tags)
        $jsonPath = '$.' . $arrayKey;

        switch ($platform) {
            case 'MySQLi':
                // MySQL syntax
                return $this->where("JSON_CONTAINS(JSON_EXTRACT(properties, '{$jsonPath}'), ?)", $encodedValue);

            case 'Postgre':
                // PostgreSQL syntax
                $path = str_replace('.', '->', $arrayKey);

                return $this->where("properties->'{$path}' @> ?::jsonb", $encodedValue);

            case 'SQLite3':
                // SQLite syntax - limited support, fallback to string comparison
                return $this->where("json_extract(properties, '{$jsonPath}') LIKE ?", '%' . $value . '%');

            case 'SQLSRV':
                // SQL Server syntax
                $path = '$.' . $arrayKey;

                return $this->where("JSON_QUERY(properties, '{$path}') LIKE ?", '%' . $value . '%');

            default:
                // Fallback to string-based comparison (less efficient but more compatible)
                return $this->where('properties LIKE ?', '%"' . $arrayKey . '"%' . $value . '%');
        }
    }

    /**
     * Filter assets by creation date
     *
     * @param string $date     The date to filter by (in format matching dateFormat)
     * @param string $operator Comparison operator (=, >, <, >=, <=)
     *
     * @return $this
     */
    public function filterByCreatedAt(string $date, string $operator = '='): self
    {
        return $this->where('created_at ' . $operator, $date);
    }

    /**
     * Filter assets by update date
     *
     * @param string $date     The date to filter by (in format matching dateFormat)
     * @param string $operator Comparison operator (=, >, <, >=, <=)
     *
     * @return $this
     */
    public function filterByUpdatedAt(string $date, string $operator = '='): self
    {
        return $this->where('updated_at ' . $operator, $date);
    }

    /**
     * Filter assets by name pattern (using LIKE)
     *
     * @param string $pattern The pattern to search for
     *
     * @return $this
     */
    public function filterByNameLike(string $pattern): self
    {
        return $this->like('name', $pattern);
    }

    /**
     * Filter assets by file name pattern (using LIKE)
     *
     * @param string $pattern The pattern to search for
     *
     * @return $this
     */
    public function filterByFileNameLike(string $pattern): self
    {
        return $this->like('file_name', $pattern);
    }

    /**
     * Filter assets by size range
     *
     * @param int $minSize The minimum size
     * @param int $maxSize The maximum size
     *
     * @return $this
     */
    public function filterBySizeRange(int $minSize, int $maxSize): self
    {
        return $this->where('size >=', $minSize)->where('size <=', $maxSize);
    }

    /**
     * Filter assets by date range
     *
     * @param string $startDate The start date (in format matching dateFormat)
     * @param string $endDate   The end date (in format matching dateFormat)
     * @param string $dateField The date field to filter by (created_at or updated_at)
     *
     * @return $this
     */
    public function filterByDateRange(string $startDate, string $endDate, string $dateField = 'created_at'): self
    {
        return $this->where($dateField . ' >=', $startDate)->where($dateField . ' <=', $endDate);
    }

    /**
     * Filter assets by collection
     *
     * @param AssetCollectionDefinitionInterface|class-string<AssetCollectionDefinitionInterface> $collection The collection to filter by
     *
     * @return $this
     */
    public function whereCollection(AssetCollectionDefinitionInterface|string $collection): self
    {
        if (is_string($collection)) {
            AssetCollectionDefinitionFactory::validateStringClass($collection);

            $collectionHash = md5($collection);
        } else {
            $collectionHash = md5($collection::class);
        }

        $this->where('collection', $collectionHash);

        return $this;
    }

    /**
     * Filter assets by entity type
     *
     * @param class-string<Entity&UseAssetConnectTrait>|Entity&UseAssetConnectTrait $entityType The entity type to filter by
     *
     * @return $this
     */
    public function whereEntityType(Entity|string $entityType): self
    {
        $entityTypeHash = is_string($entityType) ? md5($entityType) : md5($entityType::class);

        $this->where('entity_type', $entityTypeHash);

        return $this;
    }

    public static function init(bool $getShared = true, ?ConnectionInterface &$conn = null): AssetModel
    {
        $modelClass = config('Asset')->assetModel ?? static::class;

        // Validate that the model class is a valid AssetModel subclass
        if (! is_subclass_of($modelClass, self::class) && $modelClass !== self::class) {
            throw new RuntimeException('Asset model class must extend ' . self::class);
        }

        $model = model($modelClass, $getShared, $conn);

        // Ensure the model is an instance of AssetModel or a subclass of AssetModel
        if (! $model instanceof AssetModel && ! is_subclass_of($model, self::class)) {
            throw new RuntimeException('Asset model must be an instance of ' . self::class . ' or a subclass of it');
        }

        // Ensure the return type is Asset or a subclass of Asset
        if (! is_subclass_of($model->returnType, Asset::class) && $model->returnType !== Asset::class) {
            throw new RuntimeException('Asset model return type must be Asset or a subclass of Asset');
        }

        return $model;
    }
}
