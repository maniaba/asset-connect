<?php

declare(strict_types=1);

namespace Tests\Support;

use CodeIgniter\Config\Factories;
use Maniaba\AssetConnect\Asset\Asset;
use Maniaba\AssetConnect\AssetConnect;
use Maniaba\AssetConnect\Config\Asset as AssetConfig;
use Tests\Support\Config\TestAssetConfig;
use Tests\Support\Entities\FakeAssetEntity;
use Tests\Support\Models\FakeAssetEntityModel;

abstract class AssetConnectFeatureTestCase extends DatabaseTestCase
{
    /**
     * @var list<string>
     */
    protected array $migrationNamespaces = [
        'Maniaba\\AssetConnect',
        'Tests\\Support',
    ];

    protected TestAssetConfig $assetConfig;
    protected FakeAssetEntityModel $fakeEntityModel;
    protected string $publicStorageRoot;
    protected string $protectedStorageRoot;
    protected string $sourceFilesRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeEntityModel = new FakeAssetEntityModel($this->db);
    }

    protected function tearDown(): void
    {
        if (isset($this->sourceFilesRoot)) {
            $this->removeDirectory(dirname($this->sourceFilesRoot));
        }

        parent::tearDown();
    }

    protected function configureDatabaseTest(): void
    {
        $root = HOMEPATH . 'build/asset-connect-feature';

        $this->publicStorageRoot    = $root . DIRECTORY_SEPARATOR . 'public';
        $this->protectedStorageRoot = $root . DIRECTORY_SEPARATOR . 'protected';
        $this->sourceFilesRoot      = $root . DIRECTORY_SEPARATOR . 'source';

        $this->removeDirectory($root);
        mkdir($this->publicStorageRoot, 0755, true);
        mkdir($this->protectedStorageRoot, 0755, true);
        mkdir($this->sourceFilesRoot, 0755, true);

        $this->assetConfig           = new TestAssetConfig();
        $this->assetConfig->DBGroup  = 'tests';
        $this->assetConfig->storages = [
            'public' => [
                'driver'     => 'local',
                'root'       => $this->publicStorageRoot,
                'public_url' => 'assets/storage',
                'visibility' => 'public',
            ],
            'protected' => [
                'driver'     => 'local',
                'root'       => $this->protectedStorageRoot,
                'visibility' => 'protected',
            ],
        ];

        Factories::injectMock('config', AssetConfig::class, $this->assetConfig);
        Factories::injectMock('config', 'Asset', $this->assetConfig);
    }

    protected function createFakeEntity(string $title = 'Fake entity'): FakeAssetEntity
    {
        $id = $this->fakeEntityModel->insert(['title' => $title], true);

        $this->assertIsInt($id);

        $entity = $this->fakeEntityModel->find($id);

        $this->assertInstanceOf(FakeAssetEntity::class, $entity);
        $this->assertInstanceOf(AssetConnect::class, $entity->assetConnectInstance());

        return $entity;
    }

    protected function createSourceFile(string $fileName, string $contents): string
    {
        $path = $this->sourceFilesRoot . DIRECTORY_SEPARATOR . $fileName;
        $dir  = dirname($path);

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, $contents);

        return $path;
    }

    protected function assertAssetWasStoredForEntity(Asset $asset, FakeAssetEntity $entity, string $collectionKey): void
    {
        $this->assertGreaterThan(0, $asset->id);
        $this->assertSame($entity->id, $asset->entity_id);
        $this->assertSame($this->assetConfig->getEntityTypeKey(FakeAssetEntity::class), $asset->entity_type);
        $this->assertSame($collectionKey, $asset->collection);
        $this->assertSame('public', $asset->storage);
        $this->assertIsRelativeStoragePath($asset->path);
        $this->assertAssetFileExists($asset);
        $this->assertAssetRowExists($asset);
    }

    protected function assertIsRelativeStoragePath(string $path): void
    {
        $this->assertNotSame('', $path);
        $this->assertStringStartsNotWith('/', $path);
        $this->assertStringNotContainsString($this->publicStorageRoot, $path);
        $this->assertStringNotContainsString($this->protectedStorageRoot, $path);
    }

    protected function assertAssetFileExists(Asset $asset): void
    {
        $this->assertFileExists($this->storagePath($asset));
    }

    protected function assertAssetFileContains(Asset $asset, string $contents): void
    {
        $this->assertSame($contents, file_get_contents($this->storagePath($asset)));
    }

    protected function assertAssetRowExists(Asset $asset): void
    {
        $this->seeInDatabase($this->tables['assets'], [
            'id'      => $asset->id,
            'storage' => $asset->storage,
            'path'    => $asset->path,
        ]);
    }

    protected function assertAssetWasSoftDeleted(Asset $asset): void
    {
        $row = $this->db->table($this->tables['assets'])
            ->where('id', $asset->id)
            ->get()
            ->getRowArray();

        $this->assertIsArray($row);
        $this->assertNotNull($row['deleted_at']);
    }

    /**
     * @return list<Asset>
     */
    protected function assertEntityAssetCount(FakeAssetEntity $entity, int $expectedCount, ?string $collection = null): array
    {
        $assets = $entity->getAssets($collection);

        $this->assertCount($expectedCount, $assets);

        return array_values($assets);
    }

    protected function storagePath(Asset $asset): string
    {
        return $this->storagePathFor($asset->storage, $asset->path);
    }

    protected function storagePathFor(string $storageName, string $path): string
    {
        $storage = $this->assetConfig->storages[$storageName] ?? null;

        $this->assertIsArray($storage);

        $root = $storage['root'] ?? null;

        $this->assertIsString($root);

        return rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
    }

    protected function removeDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $items = scandir($directory);
        $this->assertIsArray($items);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $item;

            if (is_dir($path)) {
                $this->removeDirectory($path);

                continue;
            }

            unlink($path);
        }

        rmdir($directory);
    }
}
