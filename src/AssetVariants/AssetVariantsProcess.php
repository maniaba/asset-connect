<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\AssetVariants;

use CodeIgniter\Queue\Config\Services;
use Maniaba\FileConnect\Asset\Asset;
use Maniaba\FileConnect\Asset\Interfaces\AssetCollectionDefinitionInterface;
use Maniaba\FileConnect\AssetVariants\Interfaces\AssetVariantsInterface;
use Maniaba\FileConnect\Exceptions\FileVariantException;
use Throwable;

final class AssetVariantsProcess
{
    public const QUEUE_NAME  = 'asset_queue';
    public const JOB_HANDLER = 'asset_connect';

    public static function onQueue(Asset &$asset, AssetCollectionDefinitionInterface $definition, mixed ...$definitionArguments): void
    {
        log_message('info', 'Asset variants processing is queued for asset ID: {id}', ['id' => $asset->id]);

        // You can dispatch a job to process the variants here if you have a queue system set up.
        /** @var \Maniaba\FileConnect\Config\Asset $config */
        $config     = config('Asset');
        $queue      = $config->queue['name'] ?? self::QUEUE_NAME;
        $jobHandler = $config->queue['jobHandler']['name'] ?? self::JOB_HANDLER;

        /** @var bool $result */
        $result = Services::queue()->push($queue, $jobHandler, [
            'definition'          => $definition::class,
            'definitionArguments' => $definitionArguments,
            'assetId'             => $asset->id,
        ]);

        if (! $result) {
            log_message('error', 'Failed to queue asset variants processing for asset ID: {id}', ['id' => $asset->id]);

            throw new FileVariantException('Failed to queue asset variants processing.');
        }
    }

    /**
     * Processes the asset variants based on the provided definition and storage path.
     *
     * @param AssetCollectionDefinitionInterface&AssetVariantsInterface $definition The asset collection definition.
     * @param Asset                                                     &$asset     The asset to process.
     *
     * @throws FileVariantException If an error occurs during variant processing.
     */
    public static function run(Asset &$asset, AssetCollectionDefinitionInterface&AssetVariantsInterface $definition): void
    {
        $assetVariants = new AssetVariantsProcessor(
            $asset,
        );

        try {
            $definition->variants($assetVariants, $asset);
        } catch (FileVariantException $e) {
            // If the exception is already a FileVariantException, we can rethrow it
            throw $e;
        } catch (Throwable $e) {
            // If the exception is not a FileVariantException, we wrap it in one
            throw new FileVariantException($e->getMessage(), $e->getMessage(), $e->getCode(), $e);
        }
    }
}
