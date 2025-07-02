<?php

declare(strict_types=1);

/** @var RouteCollection $routes */

use CodeIgniter\Router\RouteCollection;
use Maniaba\FileConnect\Controllers\AssetConnectController;

$routes->group('assets', static function ($routes) {
    $routes->get('test/uploads/(:num)/meho/(:segment)/(:segment)', [AssetConnectController::class, 'show/$1/$2/$3'], [
        'as' => 'asset-connect.show',
    ]);

    $routes->get('test/(:segment)/(:num)/meho/token/(:segment)/(:segment)', [AssetConnectController::class, 'temporary/$1'], [
        'as' => 'asset-connect.temporary',
    ]);
});
