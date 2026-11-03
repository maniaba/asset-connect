<?php

declare(strict_types=1);

$assetConnectSourcePath = __DIR__ . DIRECTORY_SEPARATOR . 'src';

if (class_exists(\CodeIgniter\Config\Services::class, false)) {
    \CodeIgniter\Config\Services::autoloader()
        ->addNamespace('Maniaba\\AssetConnect', $assetConnectSourcePath);

    return;
}

spl_autoload_register(static function (string $class) use ($assetConnectSourcePath): void {
    $prefix = 'Maniaba\\AssetConnect\\';

    if (! str_starts_with($class, $prefix)) {
        return;
    }

    $relativeClassPath = substr($class, strlen($prefix));
    $file              = $assetConnectSourcePath . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClassPath) . '.php';

    if (is_file($file)) {
        require $file;
    }
});
