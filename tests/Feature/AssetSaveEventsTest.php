<?php

declare(strict_types=1);

namespace Tests\Feature;

use CodeIgniter\Events\Events;
use Maniaba\AssetConnect\Asset\Asset;
use Maniaba\AssetConnect\Events\AssetCreated;
use Maniaba\AssetConnect\Events\AssetUpdated;
use Tests\Support\AssetCollections\FakeDocumentCollection;
use Tests\Support\AssetConnectFeatureTestCase;
use Tests\Support\Entities\FakeAssetEntity;

/**
 * @internal
 */
final class AssetSaveEventsTest extends AssetConnectFeatureTestCase
{
    public function testSaveDispatchesUpdatedEventForExistingAsset(): void
    {
        $entity = $this->createFakeEntity();
        $asset  = $entity->addAsset($this->createSourceFile('update-event.txt', 'update event'))
            ->usingFileName('update-event.txt')
            ->preservingOriginal()
            ->toAssetCollection(FakeDocumentCollection::class);

        $createdEvent = null;
        $updatedEvent = null;

        $this->resetAssetSaveEventListeners();

        Events::on(AssetCreated::name(), static function (AssetCreated $event) use (&$createdEvent): void {
            $createdEvent = $event;
        });
        Events::on(AssetUpdated::name(), static function (AssetUpdated $event) use (&$updatedEvent): void {
            $updatedEvent = $event;
        });

        try {
            $asset->name = 'Updated Event Document';

            $this->assertTrue($asset->save());

            $this->assertNotInstanceOf(AssetCreated::class, $createdEvent);
            $this->assertInstanceOf(AssetUpdated::class, $updatedEvent);
            $this->assertSame($asset->id, $updatedEvent->getAsset()->id);
        } finally {
            $this->resetAssetSaveEventListeners();
        }
    }



    private function resetAssetSaveEventListeners(): void
    {
        Events::removeAllListeners(AssetCreated::name());
        Events::removeAllListeners(AssetUpdated::name());
    }
}
