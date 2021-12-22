<?php

namespace App\Providers;

use App\Enums\SettingType;
use App\Repositories\SettingRepository;
use App\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

class SettingsServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('settings', function ($app) {
            return new Settings($app);
        });

        $this->app->singleton(\App\Contracts\Settings::class, function ($app) {
            return $app['settings'];
        });
    }

    /**
     * @param SettingRepository $settingRepository
     *
     * @return void
     */
    public function boot(SettingRepository $settingRepository)
    {
        $this->app['settings']->viaRequest(function (Request $request) use ($settingRepository) {
            return $settingRepository->getAllByType(SettingType::API)->keyBy('key');
        });
    }
}
