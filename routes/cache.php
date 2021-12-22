<?php

use App\Http\Response;
use App\Repositories\DistrictRepository;
use App\Repositories\MediaRepository;
use App\Repositories\PropertyImpressionRepository;
use App\Repositories\PropertyRepository;
use App\Repositories\SettingRepository;
use App\Repositories\WidgetRepository;
use App\Repositories\QuestionRepository;
use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Router;
use App\Repositories\PostRepository;
use App\Repositories\PostMetaRepository;

/**
 * @var Router $router
 */
$router->group(['middleware' => 'client-auth'], function () use ($router) {
    $router->delete('/v1/users/{id}/purgeCache', function (\App\Repositories\UserRepository $userRepository, $id) {
        $userRepository->purgeCacheByUserId($id);

        return new Response();
    });

    $router->delete('/v1/settings', function (SettingRepository $settingRepository) {
        $settingRepository->purgeAllCache();

        return new Response();
    });
});
