<?php

namespace App\Factories;

use App\Entities\Client;

class ClientFactory extends Factory
{
    /**
     *
     * @param \stdClass $entity
     *
     * @return Client
     */
    public function make(\stdClass $entity)
    {
        $client = new Client();

        $client->setId($entity->id ?? null);
        $client->setTitle($entity->title ?? null);
        $client->setToken($entity->token ?? null);
        $client->setExpiredAt($entity->expired_at ?? null);
        $client->setCreatedAt($entity->created_at ?? null);
        $client->setUpdatedAt($entity->updated_at ?? null);
        $client->setDeletedAt($entity->deleted_at ?? null);

        return $client;
    }
}
