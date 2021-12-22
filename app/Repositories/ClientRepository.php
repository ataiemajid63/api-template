<?php

namespace App\Repositories;

use App\Repositories\Contracts\IClientRepository;
use App\Repositories\Mysql\ClientRepository as ClientMysqlRepository;
use App\Repositories\Redis\ClientRepository as ClientRedisRepository;

class ClientRepository extends Repository implements IClientRepository
{
    private $clientMysqlRepository;
    private $clientRedisRepository;

    public function __construct(ClientMysqlRepository $clientMysqlRepository,ClientRedisRepository $clientRedisRepository)
    {
        parent::__construct();

        $this->clientMysqlRepository = $clientMysqlRepository;
        $this->clientRedisRepository = $clientRedisRepository;
    }

    public function getOneByToken($token, $withTrashed = false)
    {
        $client = $this->clientRedisRepository->getOneByToken($token, $withTrashed);

        if(is_null($client)) {
            $client = $this->clientMysqlRepository->getOneByToken($token, $withTrashed);

            if(!is_null($client)) {
                $this->clientRedisRepository->insert($client);

                $key = $this->clientRedisRepository->key('token', $token);
                $ttl = $this->clientRedisRepository->ttl();

                $this->clientRedisRepository->set($key, $client->getId(), $ttl);
            }
        }

        return $client;
    }
}
