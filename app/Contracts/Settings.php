<?php

namespace App\Contracts;

use App\Entities\Setting;
use Illuminate\Support\Collection;

interface Settings
{
    /**
     * Determine if key exists.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has($key);

    /**
     * Get the setting related to the key.
     *
     * @param string $key
     *
     * @return App\Entities\Setting|null
     */
    public function get($key): ?Setting;

    /**
     * Get the value related to the key.
     *
     * @param string $key
     *
     * @return string
     */
    public function value($key);

    /**
     * Add new setting
     *
     * @param App\Entities\Setting
     *
     * @return void
     */
    public function add(Setting $setting);

    /**
     * Set the settings collection
     *
     * @param App\Entities\Setting[]|Collection $settings
     *
     * @return void
     */
    public function apply(Collection $settings);

    /**
     * Set the settings via request
     *
     * @param Closure $callback
     *
     * @return void
     */
    public function viaRequest($callback);
}
