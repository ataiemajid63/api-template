<?php

namespace App\Repositories\Mysql;

use App\Factories\ClientFactory;

class ClientRepository extends Repository
{
    public function __construct()
    {
        parent::__construct();

        $this->table = 'api_clients';
    }

    public function getOneByToken($token, $withTrashed = false)
    {
        $query = $this->query();

        if ($withTrashed) {
            $query->whereNull('deleted_at');
        }

        $query->where('token', '=', $token);

        $entity = $query->first();

        $client = $entity ? (new ClientFactory())->make($entity) : null;

        return $client;
    }
}
