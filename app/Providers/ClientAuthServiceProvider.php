<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Client;
use App\Repositories\ClientRepository;
use Illuminate\Http\Request;

class ClientAuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('client', function ($app) {
            return new Client($app);
        });

        $this->app->singleton(\App\Contracts\Client::class, function ($app) {
            return $app['client'];
        });
    }

    /**
     * Boot the authentication services for the application.
     *
     * @return void
     */
    public function boot(ClientRepository $clientRepository)
    {
        $this->app['client']->viaRequest(function (Request $request) use ($clientRepository) {
            if ($request->hasHeader('Client-Token')) {

                $clientToken = $request->header('Client-Token');
                $client = $clientRepository->getOneByToken($clientToken);

                return $client;
            }

            $routeResolver = $request->getRouteResolver();
            $params = $routeResolver->__invoke()[2];

            if(isset($params['client-token'])) {
                $clientToken = $params['client-token'];

                $client = $clientRepository->getOneByToken($clientToken);

                return $client;
            }

            return null;
        });
    }
}
