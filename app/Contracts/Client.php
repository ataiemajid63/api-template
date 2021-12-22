<?php

namespace App\Contracts;

interface Client
{
    /**
     * Determine if the current client is authenticated.
     *
     * @return bool
     */
    public function check();

    /**
     * Get the currently authenticated client.
     *
     * @return App\Model\Entities\Client|null
     */
    public function fetch();

    /**
     * Set the current client
     *
     * @param App\Model\Entities\Client $client
     *
     * @return void
     */
    public function apply($client);

    /**
     * Get the ID for the currently authenticated client.
     *
     * @return integer|null
     */
    public function id();

    /**
     * Set the current client via request
     *
     * @param Closure $callback
     *
     * @return void
     */
    public function viaRequest($callback);
}
