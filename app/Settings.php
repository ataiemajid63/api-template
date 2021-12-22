<?php

namespace App;

use App\Contracts\Settings as SettingsContract;
use App\Entities\Setting;
use Illuminate\Support\Collection;


class Settings implements SettingsContract
{
    /**
     * The application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     *
     * @var Setting[]|Collection
     */
    protected $settings;

    /**
     * @var callback
     */
    protected $callback;

    public function __construct($app)
    {
        $this->app = $app;
        $this->settings = collect();
    }

    public function has($key)
    {
        return $this->settings->has($key);
    }

    public function get($key): ?Setting
    {
        if($this->settings->isEmpty()) {
            $callback = $this->callback;
            $this->apply($callback($this->app['request']));
        }

        return $this->settings->get($key);
    }

    public function value($key)
    {
        $setting = $this->get($key);

        return $setting ? $setting->getValue(): null;
    }

    public function add(Setting $setting)
    {
        $this->settings->put($setting->getKey(), $setting);
    }

    public function apply(Collection $settings)
    {
        $this->settings = $settings;
    }

    public function viaRequest($callback)
    {
        $this->callback = $callback;
    }
}
