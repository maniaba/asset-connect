<?php

declare(strict_types=1);

namespace Tests\Jobs;

use JsonException;
use Maniaba\AssetConnect\Jobs\AssetConnectJob;
use Tests\Support\AssetCollections\FakeDocumentCollection;
use Tests\Support\AssetConnectFeatureTestCase;
use Tests\Support\Entities\FakeAssetEntity;

/**
 * @internal
 */
final class AssetConnectJobTest extends AssetConnectFeatureTestCase
{
    /**
     * @throws JsonException
     */
    public function testCleanGarbageDeletesSoftDeletedProtectedAssetWithDatabaseShapedVariants(): void
    {
        $assetPath  = '2026-07-07/67fac7aa238a42720c38/a145acb5-d121-455a-93fa-a9d031df900c.jpeg';
        $thumbPath  = '2026-07-07/67fac7aa238a42720c38/variants/a145acb5-d121-455a-93fa-a9d031df900c-thumb.webp';
        $mediumPath = '2026-07-07/67fac7aa238a42720c38/variants/a145acb5-d121-455a-93fa-a9d031df900c-medium.webp';

        $this->writeStorageFile('protected', $assetPath, 'asset contents');
        $this->writeStorageFile('protected', $thumbPath, 'thumb contents');
        $this->writeStorageFile('protected', $mediumPath, 'medium contents');

        $assetId = $this->insertSoftDeletedAsset('protected', $assetPath, [
            'user_custom'    => [],
            'internal'       => ['asset_entity_link' => 'fake_asset_entity'],
            'asset_variants' => [
                'thumb' => [
                    'name'      => 'thumb',
                    'storage'   => 'protected',
                    'path'      => $thumbPath,
                    'size'      => 30834,
                    'processed' => true,
                ],
                'medium' => [
                    'name'      => 'medium',
                    'storage'   => 'protected',
                    'path'      => $mediumPath,
                    'size'      => 275868,
                    'processed' => true,
                ],
            ],
        ]);

        (new AssetConnectJob([]))->cleanGarbage();

        $this->assertFileDoesNotExist($this->storagePathFor('protected', $assetPath));
        $this->assertFileDoesNotExist($this->storagePathFor('protected', $thumbPath));
        $this->assertFileDoesNotExist($this->storagePathFor('protected', $mediumPath));
        $this->assertAssetRowWasForceDeleted($assetId);
    }

    /**
     * @throws JsonException
     */
    public function testCleanGarbageDeletesVariantsFromStorageDeclaredInMetadata(): void
    {
        $assetPath   = '2026-07-07/c6aa632dca28ed322ee1/document.txt';
        $variantPath = '2026-07-07/c6aa632dca28ed322ee1/variants/document-thumb.webp';

        $this->writeStorageFile('public', $assetPath, 'asset contents');
        $this->writeStorageFile('protected', $variantPath, 'variant contents');

        $assetId = $this->insertSoftDeletedAsset('public', $assetPath, [
            'user_custom'    => [],
            'internal'       => ['asset_entity_link' => 'fake_asset_entity'],
            'asset_variants' => [
                'thumb' => [
                    'name'      => 'thumb',
                    'storage'   => 'protected',
                    'path'      => $variantPath,
                    'size'      => 30834,
                    'processed' => true,
                ],
            ],
        ]);

        (new AssetConnectJob([]))->cleanGarbage();

        $this->assertFileDoesNotExist($this->storagePathFor('public', $assetPath));
        $this->assertFileDoesNotExist($this->storagePathFor('protected', $variantPath));
        $this->assertAssetRowWasForceDeleted($assetId);
    }

    /**
     * @throws JsonException
     */
    public function testCleanGarbageKeepsSoftDeletedRowWhenStorageDiskCannotBeResolved(): void
    {
        $assetId = $this->insertSoftDeletedAsset('missing', 'missing-storage/file.txt', [
            'user_custom'    => [],
            'internal'       => ['asset_entity_link' => 'fake_asset_entity'],
            'asset_variants' => [],
        ]);

        (new AssetConnectJob([]))->cleanGarbage();

        $this->seeInDatabase($this->tables['assets'], [
            'id'         => $assetId,
            'storage'    => 'missing',
            'deleted_at' => '2026-07-07 12:31:43',
        ]);
    }

    private function writeStorageFile(string $storageName, string $path, string $contents): void
    {
        $absolutePath = $this->storagePathFor($storageName, $path);
        $directory    = dirname($absolutePath);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $this->assertNotFalse(file_put_contents($absolutePath, $contents));
    }

    /**
     * @param array<string, mixed> $metadata
     *
     * @throws JsonException
     */
    private function insertSoftDeletedAsset(string $storage, string $path, array $metadata): int
    {
        $entity = $this->createFakeEntity();

        $this->db->table($this->tables['assets'])->insert([
            'entity_type' => $this->assetConfig->getEntityTypeKey(FakeAssetEntity::class),
            'entity_id'   => $entity->id,
            'collection'  => $this->assetConfig->getCollectionKey(FakeDocumentCollection::class),
            'storage'     => $storage,
            'name'        => pathinfo($path, PATHINFO_FILENAME),
            'file_name'   => basename($path),
            'mime_type'   => 'image/jpeg',
            'size'        => 123,
            'path'        => $path,
            'order'       => 0,
            'metadata'    => json_encode($metadata, JSON_THROW_ON_ERROR),
            'created_at'  => '2026-07-07 12:31:39',
            'updated_at'  => '2026-07-07 12:31:43',
            'deleted_at'  => '2026-07-07 12:31:43',
        ]);

        return (int) $this->db->insertID();
    }

    private function assertAssetRowWasForceDeleted(int $assetId): void
    {
        $row = $this->db->table($this->tables['assets'])
            ->where('id', $assetId)
            ->get()
            ->getRowArray();

        $this->assertNull($row);
    }
}
