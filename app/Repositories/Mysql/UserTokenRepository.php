<?php

namespace App\Repositories\Mysql;

use App\Entities\UserToken;
use App\Factories\UserTokenFactory;
use App\Repositories\Contracts\IUserTokenRepository;

class UserTokenRepository extends Repository implements IUserTokenRepository
{
    public function __construct()
    {
        parent::__construct();

        $this->table = 'api_user_tokens';
    }

    public function insert(UserToken $userToken): ?UserToken
    {
        $query = $this->query();

        $id = $query->insertGetId([
            'id' => $userToken->getId(),
            'user_id' => $userToken->getUserId(),
            'token' => $userToken->getToken(),
            'device_id' => $userToken->getDeviceId(),
            'device_density' => $userToken->getDeviceDensity(),
            'device_model' => $userToken->getDeviceModel(),
            'device_language' => $userToken->getDeviceLanguage(),
            'network_type' => $userToken->getNetworkType(),
            'screen_width' => $userToken->getScreenWidth(),
            'screen_height' => $userToken->getScreenHeight(),
            'ip' => $userToken->getIp(),
            'os' => $userToken->getOs(),
            'os_version' => $userToken->getOsVersion(),
            'api_version' => $userToken->getApiVersion(),
            'client_version' => $userToken->getClientVersion(),
            'created_at' => $userToken->getCreatedAt(),
            'updated_at' => $userToken->getUpdatedAt(),
            'expired_at' => $userToken->getExpiredAt(),
        ]);

        $userToken->setId($id);

        return $userToken;
    }

    public function getOneByToken($token): ?UserToken
    {
        $query = $this->query();

        $query->where('token', $token);
        $query->where(function ($query) {
            $query->where('expired_at', '>', time());
            $query->orWhere('expired_at', '=', 0);

            return $query;
        });

        $entity = $query->first();

        return $entity ? (new UserTokenFactory())->make($entity) : null;
    }

    public function expireByUserId($userId)
    {
        $query = $this->query();

        $query->where('user_id', $userId);
        $query->where(function ($query) {
            $query->where('expired_at', '>', time());
            $query->where('expired_at', '=', 0);

            return $query;
        });
        $query->update([
            'expired_at' => time(),
            'updated_at' => time(),
        ]);
    }

    public function expireByToken($token)
    {
        $query = $this->query();

        $query->where('token', $token);
        $query->where(function ($query) {
            $query->where('expired_at', '>', time());
            $query->where('expired_at', '=', 0);

            return $query;
        });
        $query->update([
            'expired_at' => time(),
            'updated_at' => time(),
        ]);
    }
}
