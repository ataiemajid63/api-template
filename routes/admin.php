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

$router->group(['middleware' => 'client-auth'], function () use ($router) {

    $router->group(['middleware' => 'user-auth'], function () use ($router) {

    });

    $router->post('/v1/users/update', 'Admin\V1\UsersController@update');
});

