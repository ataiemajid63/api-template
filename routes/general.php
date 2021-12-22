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

use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;

$router->get('/opcache', function () use ($router) {
    dd(opcache_get_status(), opcache_get_configuration());
});

$router->get('/debugLogs', function (Request $request) use ($router) {
    /**
     * @var Builder $query
     */
    $query = app('db')->connection('pgsql')->table('debug_logs');

    $query->select('serial');
    $query->selectRaw('round((max(microtime) - min(microtime)) * 1000) as duration');
    $query->groupBy('serial');

    if ($request->has('sort')) {
        $query->orderByDesc('duration');
    }

    $count = $request->get('count', 50);
    $offset = ($request->get('page', 1) - 1) * $count;

    $query->offset($offset);
    $query->limit($count);

    $result = $query->get();

    return view('debug_logs', ['logs' => $result]);
});

$router->get('/debugLogs/{serial}/get', function (Request $request, $serial) use ($router) {
    /**
     * @var Builder $query
     */
    $query = app('db')->connection('pgsql')->table('debug_logs');

    $query->where('serial', $serial);

    $result = $query->get();

    return view('debug_log', ['logs' => $result, 'serial' => $serial]);
});

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['middleware' => 'client-auth'], function () use ($router) {

    $router->get('/v1/users/usernameInfo/{username}', 'V1\UsersController@getUsernameInfo');
    $router->post('/v1/users/loginByPassword', 'V1\UsersController@loginByPassword');

    $router->group(['middleware' => 'user-auth'], function () use ($router) {
        $router->get('/v1/users/info', 'V1\UsersController@getUserInfo');
        $router->post('/v1/users/update', 'V1\UsersController@update');
    });
});

