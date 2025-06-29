<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\AssetCollection;

use CodeIgniter\Queue\Config\Services;
use CodeIgniter\Queue\QueuePushResult;
use Maniaba\FileConnect\Asset\Asset;
use Maniaba\FileConnect\Exceptions\FileVariantException;
use Maniaba\FileConnect\Interfaces\Asset\AssetCollectionDefinitionInterface;
use Throwable;

final class AssetVariantsProcess
{
    public const QUEUE_NAME  = 'asset_queue';
    public const JOB_HANDLER = 'asset_connect';

    public static function onQueue(string $storagePathVariants, Asset &$asset, AssetCollectionDefinitionInterface $definition, mixed ...$definitionArguments): void
    {
        log_message('info', 'Asset variants processing is queued for asset ID: {id}', ['id' => $asset->id]);

        // You can dispatch a job to process the variants here if you have a queue system set up.
        /** @var \Maniaba\FileConnect\Config\Asset $config */
        $config     = config('Asset');
        $queue      = $config->queue['name'] ?? self::QUEUE_NAME;
        $jobHandler = $config->queue['jobHandler']['name'] ?? self::JOB_HANDLER;

        /** @var QueuePushResult $result */
        $result = Services::queue()->push($queue, $jobHandler, [
            'definition'          => $definition::class,
            'definitionArguments' => $definitionArguments,
            'storagePath'         => $storagePathVariants,
            'assetId'             => $asset->id,
        ]);

        if (! $result->getStatus()) {
            log_message('error', 'Failed to queue asset variants processing for asset ID: {id}', ['id' => $asset->id]);

            throw new FileVariantException('Failed to queue asset variants processing.');
        }
    }

    /**
     * Processes the asset variants based on the provided definition and storage path.
     *
     * @param AssetCollectionDefinitionInterface $definition         The asset collection definition.
     * @param string                             $storagePathVarians The storage path for the variants.
     * @param Asset                              $asset              The asset to process.
     *
     * @throws FileVariantException If an error occurs during variant processing.
     */
    public static function run(AssetCollectionDefinitionInterface $definition, string $storagePathVarians, Asset &$asset): void
    {
        $assetVariants = new AssetVariants(
            $storagePathVarians,
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

        // Validate the variants after processing
        $variants = $asset->properties->fileVariant->getVariants();

        foreach ($variants as &$variant) {
            if (file_exists($variant->path)) {
                $variant->processed = true;
                $variant->size      = filesize($variant->path);
            }
        }
    }
}
