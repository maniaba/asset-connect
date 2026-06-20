<?php

declare(strict_types=1);

namespace Tests\Feature;

use Maniaba\AssetConnect\Asset\Asset;
use Maniaba\AssetConnect\Models\AssetModel;
use Maniaba\AssetConnect\Pending\PendingAsset;
use Maniaba\AssetConnect\Pending\PendingAssetManager;
use Tests\Support\AssetCollections\FakeDocumentCollection;
use Tests\Support\AssetConnectFeatureTestCase;
use Tests\Support\Entities\FakeAssetEntity;

/**
 * @internal
 */
final class PendingAssetEntityFlowTest extends AssetConnectFeatureTestCase
{
    public function testStoredPendingAssetIdIsConsumedAndAttachedToTheTargetEntityAsset(): void
    {
        $targetEntity = $this->createFakeEntity('Target entity');
        $otherEntity  = $this->createFakeEntity('Other entity');

        $pendingSource = $this->createSourceFile('pending-contract-source.txt', 'pending contract contents');
        $pendingAsset  = PendingAsset::createFromFile($pendingSource);
        $pendingAsset
            ->usingName('Pending Contract')
            ->usingFileName('pending contract.txt')
            ->setOrder(42)
            ->withCustomProperty('workflow', 'pending-id');

        $manager = PendingAssetManager::make();
        $manager->store($pendingAsset);

        $pendingId           = $pendingAsset->id;
        $pendingFilePath     = $this->protectedStorageRoot . DIRECTORY_SEPARATOR . 'assets_pending' . DIRECTORY_SEPARATOR . $pendingId . DIRECTORY_SEPARATOR . 'file';
        $pendingMetadataPath = $this->protectedStorageRoot . DIRECTORY_SEPARATOR . 'assets_pending' . DIRECTORY_SEPARATOR . $pendingId . DIRECTORY_SEPARATOR . 'metadata.json';

        $this->assertNotSame('', $pendingId, 'Pending asset should receive a generated ID before it is attached to an entity.');
        $this->assertFileExists($pendingFilePath, 'Pending raw file should exist before the pending ID is consumed.');
        $this->assertFileExists($pendingMetadataPath, 'Pending metadata should exist before the pending ID is consumed.');
        $this->assertInstanceOf(
            PendingAsset::class,
            $manager->fetchById($pendingId),
            'Pending ID should be fetchable before it is added to an asset collection.',
        );

        $asset = $targetEntity->addAssetFromPending($pendingId)
            ->toAssetCollection(FakeDocumentCollection::class);

        $this->assertNull(
            $manager->fetchById($pendingId),
            'Pending ID should no longer be fetchable after it is added to an asset collection.',
        );
        $this->assertFileDoesNotExist($pendingFilePath, 'Pending raw file should be deleted after the pending ID is consumed.');
        $this->assertFileDoesNotExist($pendingMetadataPath, 'Pending metadata should be deleted after the pending ID is consumed.');

        $this->assertAssetWasStoredForEntity($asset, $targetEntity, 'fake_documents');
        $this->assertAssetFileContains($asset, 'pending contract contents');
        $this->assertSame('Pending Contract', $asset->name, 'Pending name should be copied to the stored asset.');
        $this->assertSame('pending-contract.txt', $asset->file_name, 'Pending file name should be sanitized and copied to the stored asset.');
        $this->assertSame(42, $asset->order, 'Pending order should be copied to the stored asset.');
        $this->assertSame('pending-id', $asset->getCustomProperty('workflow'), 'Pending custom properties should be copied to the stored asset.');
        $this->assertSame('fake-assets/documents/pending-contract.txt', $asset->path, 'Stored asset should use the target collection path.');

        $this->seeInDatabase($this->tables['assets'], [
            'id'          => $asset->id,
            'entity_type' => $this->assetConfig->getEntityTypeKey(FakeAssetEntity::class),
            'entity_id'   => $targetEntity->id,
            'collection'  => 'fake_documents',
            'storage'     => 'public',
            'name'        => 'Pending Contract',
            'file_name'   => 'pending-contract.txt',
            'path'        => 'fake-assets/documents/pending-contract.txt',
            'order'       => 42,
        ]);
        $this->dontSeeInDatabase($this->tables['assets'], [
            'id'        => $asset->id,
            'entity_id' => $otherEntity->id,
        ]);

        $targetAssets = $this->assertEntityAssetCount($targetEntity, 1, FakeDocumentCollection::class);
        $this->assertSame($asset->id, $targetAssets[0]->id, 'Target entity should expose the asset created from the pending ID.');
        $this->assertSame($asset->id, $targetEntity->getFirstAsset(FakeDocumentCollection::class)?->id, 'Target entity first asset should be the pending-derived asset.');
        $this->assertEntityAssetCount($otherEntity, 0, FakeDocumentCollection::class);

        $storedAsset = AssetModel::init(false)->find($asset->id);
        $this->assertInstanceOf(Asset::class, $storedAsset, 'Stored database row should hydrate as an Asset entity.');
        $this->assertSame($targetEntity->id, $storedAsset->entity_id, 'Stored database asset should belong to the target entity.');
        $this->assertSame('fake_documents', $storedAsset->collection, 'Stored database asset should belong to the requested collection.');
        $this->assertSame('pending-id', $storedAsset->getCustomProperty('workflow'), 'Stored database asset should retain pending custom properties.');
    }
}
