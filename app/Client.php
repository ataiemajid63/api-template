<?php

namespace App;

use App\Contracts\Client as ClientContract;
use App\Entities\Client as ClientEntity;


class Client implements ClientContract
{

    /**
     * The application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * Determine if the current client is authenticated.
     *
     * @var ClientEntity
     */
    protected $client;

    /**
     * @var callback
     */
    protected $callback;

    public function __construct($app)
    {
        $this->app = $app;
        $this->client = null;
    }

    public function check()
    {
        return !is_null($this->fetch());
    }

    public function fetch()
    {
        if(is_null($this->client)) {
            $callback = $this->callback;
            $this->apply($callback($this->app['request']));
        }

        return $this->client;
    }

    public function apply($client)
    {
        $this->client = $client;
    }

    public function id()
    {
        return $this->check() ? $this->fetch()->getId() : null;
    }

    public function viaRequest($callback)
    {
        $this->callback = $callback;
    }
}
