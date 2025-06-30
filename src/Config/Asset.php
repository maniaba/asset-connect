<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Config;

use CodeIgniter\Config\BaseConfig;
use Maniaba\FileConnect\Asset\Interfaces\AssetCollectionDefinitionInterface;
use Maniaba\FileConnect\AssetCollection\DefaultAssetCollection;
use Maniaba\FileConnect\AssetVariants\AssetVariantsProcess;
use Maniaba\FileConnect\Jobs\AssetConnectJob;
use Maniaba\FileConnect\PathGenerator\DefaultPathGenerator;
use Maniaba\FileConnect\PathGenerator\Interfaces\PathGeneratorInterface;

class Asset extends BaseConfig
{
    /**
     * --------------------------------------------------------------------
     * Customize the DB group used for each model
     * --------------------------------------------------------------------
     */
    public ?string $DBGroup = null;

    /**
     * --------------------------------------------------------------------
     * Default Asset Collection
     * --------------------------------------------------------------------
     * This is the default collection that will be used when no specific
     * collection is provided during asset creation.
     *
     * You can change this to any class that implements the AssetCollectionInterface.
     *
     * @var class-string<AssetCollectionDefinitionInterface>
     */
    public string $defaultCollection = DefaultAssetCollection::class;

    /**
     * --------------------------------------------------------------------
     * Default Path Generator
     * --------------------------------------------------------------------
     * This is the default path generator that will be used to generate
     * the storage paths for assets.
     *
     * You can change this to any class that implements the PathGeneratorInterface.
     * Also allows you to create custom path generators for specific AssetCollectionInterface overriding method pathGenerator().
     *
     * @var class-string<PathGeneratorInterface>
     */
    public string $defaultPathGenerator = DefaultPathGenerator::class;

    /**
     * --------------------------------------------------------------------
     * Customize Name of Asset Table
     * --------------------------------------------------------------------
     * Only change if you want to rename the default Asset Connect table names.
     *
     * It may be necessary to change the names of the tables for
     * security reasons, to prevent the conflict of table names,
     * the internal policy of the companies or any other reason.
     *
     * - assets                  : The table that stores the assets metadata.
     *
     * @var array<string, string>
     */
    public array $tables = [
        'assets' => 'assets',
    ];

    /**
     * --------------------------------------------------------------------
     * Queue Name
     * --------------------------------------------------------------------
     * This is the name of the queue that will be used for processing
     * asset manipulations and variants.
     */
    public array $queue = [
        'name'       => AssetVariantsProcess::QUEUE_NAME,
        'jobHandler' => [
            'name'  => AssetVariantsProcess::JOB_HANDLER,
            'class' => AssetConnectJob::class,
        ],
    ];
}
