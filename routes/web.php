<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
 */
//, 'middleware' => 'auth'
$router->group(['prefix' => 'api/asset'], function () use ($router) {
    $router->get('/', ['uses' => 'AssetController@get']);
    $router->post('/', ['uses' => 'AssetController@post']);
});
$router->group(['prefix' => 'api/playlist'], function () use ($router) {
    $router->get('/', ['uses' => 'PlaylistController@get']);
    $router->post('/', ['uses' => 'PlaylistController@post']);
});
$router->group(['prefix' => 'api/layout'], function () use ($router) {
    $router->get('/', ['uses' => 'LayoutController@get']);
    $router->post('/', ['uses' => 'LayoutController@post']);
});
$router->group(['prefix' => 'api'], function () use ($router) {
    $router->post('auth', ['uses' => 'UserController@login']);
    $router->get('menu', ['uses' => 'MenuController@get']);
    $router->get('/getLanguage/{type}/{lang}', ['uses' => 'LanguagesettingController@show']);
    $router->get('/getColumns', ['uses' => 'GridController@getColumns']);
});

