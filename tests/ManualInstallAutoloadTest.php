<?php

declare(strict_types=1);

namespace Tests;

use CodeIgniter\Config\Services;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class ManualInstallAutoloadTest extends CIUnitTestCase
{
    public function testPackageAutoloadRegistersAssetConnectNamespaceWithCodeIgniter(): void
    {
        $packageRoot   = dirname(__DIR__);
        $loader        = Services::autoloader();
        $namespace     = 'Maniaba\\AssetConnect';
        $originalPaths = $loader->getNamespace($namespace);

        $loader->removeNamespace($namespace);

        try {
            require $packageRoot . DIRECTORY_SEPARATOR . 'autoload.php';

            $this->assertContains(
                $packageRoot . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR,
                $loader->getNamespace($namespace),
                'The manual package autoload file must register the AssetConnect namespace with CodeIgniter.',
            );
        } finally {
            $loader->removeNamespace($namespace);

            if ($originalPaths !== []) {
                $loader->addNamespace([$namespace => $originalPaths]);
            }
        }
    }
}
