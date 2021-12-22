<?php

namespace App\Providers;

use App\Repositories\UserRepository;
use App\Repositories\UserTokenRepository;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Boot the authentication services for the application.
     *
     * @return void
     */
    public function boot(UserTokenRepository $userTokenRepository, UserRepository $userRepository)
    {
        // Here you may define how you wish users to be authenticated for your Lumen
        // application. The callback which receives the incoming request instance
        // should return either a User instance or null. You're free to obtain
        // the User instance via an API token or any other method necessary.

        $this->app['auth']->viaRequest('api', function ($request) use ($userTokenRepository, $userRepository) {
            if ($request->hasHeader('Auth-Token') && !empty($request->header('Auth-Token'))) {
                $token = $request->header('Auth-Token');
                $authToken = $userTokenRepository->getOneByToken($token);

                if(is_null($authToken)) {
                    return null;
                }

                $user = $userRepository->getOneById($authToken->getUserId());

                return $user;
            }

            return null;
        });
    }
}
