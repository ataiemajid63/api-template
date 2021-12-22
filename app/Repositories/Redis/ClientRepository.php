<?php

namespace App\Repositories\Redis;

use App\Entities\Client;
use App\Factories\ClientFactory;

class ClientRepository extends Repository
{
    public function __construct()
    {
        parent::__construct();

        $this->table = 'api_clients';
        $this->ttl = 24 * 60 * 60; // 1 Day
    }

    public function insert(Client $client)
    {
        $key = $this->key('id', $client->getId());
        $value = $client->toArray(true);

        $this->set($key, $value, $this->ttl);

        return $client;
    }

    public function getOneById($id, $withTrashed = false)
    {
        $key = $this->key('id', $id);

        $entity = $this->get($key, []);

        $client = $entity ? (new ClientFactory())->makeWithArray((array)$entity) : null;

        $client = ($withTrashed || is_null($client) || is_null($client->getDeletedAt())) ? $client : null;

        return $client;
    }

    public function getOneByToken($token, $withTrashed = false)
    {
        $key = $this->key('token', $token);

        $id = $this->get($key);

        return $this->getOneById($id, $withTrashed);
    }
}
