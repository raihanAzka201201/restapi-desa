<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

$routes->get('desa', 'Desa::desa');
$routes->get('desa/(:num)', 'Desa::detailDesa/$1');
$routes->post('desa', 'Desa::create');
$routes->put('desa/(:num)', 'Desa::update/$1');
$routes->delete('desa/(:num)', 'Desa::delete/$1');
