<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Models;

use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Model;
use CodeIgniter\Validation\ValidationInterface;
use Exception;
use Maniaba\FileConnect\Config\Asset;

abstract class BaseModel extends Model
{
    protected $dateFormat = 'datetime';
    protected Asset $assetConfig;

    public function __construct(?ConnectionInterface $db = null, ?ValidationInterface $validation = null)
    {
        $this->assetConfig = config('Asset');

        if ($this->assetConfig->DBGroup !== null) {
            $this->DBGroup = $this->assetConfig->DBGroup;
        }

        parent::__construct($db, $validation);
    }

    abstract protected function setConfigTableName(): string;

    /**
     * @throws Exception
     */
    protected function initialize(): void
    {
        $this->table = $this->assetConfig->tables[$this->setConfigTableName()]
            ?? throw new Exception('Table not found in Asset config');
    }

    public function getTableName(): string
    {
        return $this->table;
    }
}
