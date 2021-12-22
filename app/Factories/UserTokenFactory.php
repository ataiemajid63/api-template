<?php

namespace App\Factories;

use App\Entities\UserToken;

class UserTokenFactory extends Factory
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param \stdClass $entity
     *
     * @return UserToken
     */
    public function make(\stdClass $entity)
    {
        $userToken = new UserToken();

        $userToken->setId($entity->id ?? null);
        $userToken->setUserId($entity->user_id ?? null);
        $userToken->setToken($entity->token ?? null);
        $userToken->setDeviceId($entity->device_id ?? null);
        $userToken->setDeviceDensity($entity->device_density ?? null);
        $userToken->setDeviceModel($entity->device_model ?? null);
        $userToken->setDeviceLanguage($entity->device_language ?? null);
        $userToken->setNetworkType($entity->network_type ?? null);
        $userToken->setScreenWidth($entity->screen_width ?? null);
        $userToken->setScreenHeight($entity->screen_height ?? null);
        $userToken->setIp($entity->ip ?? null);
        $userToken->setOs($entity->os ?? null);
        $userToken->setOsVersion($entity->os_version ?? null);
        $userToken->setApiVersion($entity->api_version ?? null);
        $userToken->setClientVersion($entity->client_version ?? null);
        $userToken->setCreatedAt($entity->created_at ?? null);
        $userToken->setUpdatedAt($entity->updated_at ?? null);
        $userToken->setExpiredAt($entity->expired_at ?? null);

        return $userToken;
    }
}
