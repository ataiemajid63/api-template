<?php

namespace App\Providers;

use App\Constracts\DebugLogger as IDebugLogger;
use App\DebugLogger;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('logger', function ($app) {
            return new DebugLogger();
        });

        $this->app->singleton(IDebugLogger::class, function ($app) {
            return $app['logger'];
        });
    }
}
