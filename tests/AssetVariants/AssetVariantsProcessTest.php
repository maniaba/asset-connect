<?php

declare(strict_types=1);

namespace Tests\AssetVariants;

use CodeIgniter\Config\Factories;
use CodeIgniter\Queue\Config\Services as QueueServices;
use CodeIgniter\Queue\Interfaces\QueueInterface;
use CodeIgniter\Queue\QueuePushResult;
use CodeIgniter\Test\CIUnitTestCase;
use Maniaba\AssetConnect\Asset\Asset;
use Maniaba\AssetConnect\AssetVariants\AssetVariantsProcess;
use Maniaba\AssetConnect\Config\Asset as AssetConfig;
use Maniaba\AssetConnect\Exceptions\FileVariantException;
use RuntimeException;
use Tests\Support\AssetCollections\ThrowingVariantTestAssetCollection;

/**
 * @internal
 */
final class AssetVariantsProcessTest extends CIUnitTestCase
{
    protected function tearDown(): void
    {
        ThrowingVariantTestAssetCollection::reset();
        QueueServices::resetSingle('queue');
        Factories::reset('config');

        parent::tearDown();
    }

    public function testOnQueuePushesConfiguredPayloadToQueue(): void
    {
        $asset      = $this->createAsset();
        $definition = new ThrowingVariantTestAssetCollection();

        $this->injectQueueConfig('custom_asset_queue', 'custom_asset_job');

        $queue = $this->createMock(QueueInterface::class);
        $queue->expects($this->once())
            ->method('push')
            ->with(
                'custom_asset_queue',
                'custom_asset_job',
                [
                    'definition'          => ThrowingVariantTestAssetCollection::class,
                    'definitionArguments' => ['first-argument', ['second' => true]],
                    'assetId'             => 123,
                ],
            )
            ->willReturn(QueuePushResult::success(456));

        QueueServices::injectMock('queue', $queue);

        AssetVariantsProcess::onQueue($asset, $definition, 'first-argument', ['second' => true]);

        $this->addToAssertionCount(1);
    }

    public function testOnQueueThrowsWhenQueuePushFails(): void
    {
        $asset      = $this->createAsset();
        $definition = new ThrowingVariantTestAssetCollection();

        $this->injectQueueConfig('custom_asset_queue', 'custom_asset_job');

        $queue = $this->createMock(QueueInterface::class);
        $queue->expects($this->once())
            ->method('push')
            ->with(
                'custom_asset_queue',
                'custom_asset_job',
                $this->callback(function (array $payload): bool {
                    $this->assertSame(ThrowingVariantTestAssetCollection::class, $payload['definition'] ?? null);
                    $this->assertSame(123, $payload['assetId'] ?? null);

                    return true;
                }),
            )
            ->willReturn(QueuePushResult::failure('queue disabled'));

        QueueServices::injectMock('queue', $queue);

        try {
            AssetVariantsProcess::onQueue($asset, $definition);
            $this->fail('Queue push failure must throw FileVariantException.');
        } catch (FileVariantException $exception) {
            $this->assertSame(
                ['Failed to queue asset variants processing.'],
                $exception->errors,
                'Queue push failure should expose the asset variant queueing error.',
            );
        }
    }

    public function testRunRethrowsFileVariantException(): void
    {
        $asset      = $this->createAsset();
        $definition = new ThrowingVariantTestAssetCollection();
        $exception  = new FileVariantException('variant failure', 'variant failure');

        ThrowingVariantTestAssetCollection::$throwable = $exception;

        try {
            AssetVariantsProcess::run($asset, $definition);
            $this->fail('Asset variant process must rethrow FileVariantException from the collection definition.');
        } catch (FileVariantException $caught) {
            $this->assertSame(
                $exception,
                $caught,
                'FileVariantException should not be wrapped a second time.',
            );
            $this->assertTrue(
                ThrowingVariantTestAssetCollection::$variantsCalled,
                'Variant definition must be invoked before rethrowing the exception.',
            );
        }
    }

    public function testRunWrapsUnexpectedThrowableInFileVariantException(): void
    {
        $asset      = $this->createAsset();
        $definition = new ThrowingVariantTestAssetCollection();
        $exception  = new RuntimeException('unexpected variant failure', 123);

        ThrowingVariantTestAssetCollection::$throwable = $exception;

        try {
            AssetVariantsProcess::run($asset, $definition);
            $this->fail('Asset variant process must wrap unexpected throwables in FileVariantException.');
        } catch (FileVariantException $caught) {
            $this->assertSame(
                ['unexpected variant failure'],
                $caught->errors,
                'Wrapped exception errors should preserve the original throwable message.',
            );
            $this->assertSame(
                'unexpected variant failure',
                $caught->getMessage(),
                'Wrapped exception message should preserve the original throwable message.',
            );
            $this->assertSame(
                $exception,
                $caught->getPrevious(),
                'Wrapped exception should keep the original throwable as previous.',
            );
        }
    }

    private function createAsset(): Asset
    {
        $asset     = new Asset();
        $asset->id = 123;

        return $asset;
    }

    private function injectQueueConfig(string $queueName, string $jobHandler): void
    {
        $config = new AssetConfig();

        $config->queue = [
            'name'       => $queueName,
            'jobHandler' => [
                'name' => $jobHandler,
            ],
        ];

        Factories::injectMock('config', 'Asset', $config);
    }
}
