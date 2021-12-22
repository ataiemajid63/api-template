<?php

namespace App\Repositories\Redis;

use App\Entities\UserToken;
use App\Factories\UserTokenFactory;
use App\Repositories\Contracts\IUserTokenRepository;

class UserTokenRepository extends Repository implements IUserTokenRepository
{
    public function __construct()
    {
        parent::__construct();

        $this->table = 'api_user_tokens';
        $this->ttl = 15 * 60; // 15 Minutes
    }

    public function insert(UserToken $userToken): ?UserToken
    {
        $keyId = $this->key('id', $userToken->getId());
        $keyToken = $this->key('token', $userToken->getToken());
        $keyUserId = $this->key('userId', $userToken->getUserId());

        $this->set($keyId, $userToken->toArray(true), $this->ttl);
        $this->set($keyToken, $userToken->getId(), $this->ttl);
        $this->set($keyUserId, $userToken->getId(), $this->ttl);

        return $userToken;
    }

    public function getOneById($id): ?UserToken
    {
        $key = $this->key('id', $id);

        $entity = $this->get($key);

        return $entity ? (new UserTokenFactory())->makeWithArray((array)$entity) : null;
    }

    public function getOneByToken($token): ?UserToken
    {
        $key = $this->key('token', $token);

        $id = $this->get($key);

        return $this->getOneById($id);
    }

    public function expireByUserId($userId)
    {
        $key = $this->key('userId', $userId);
        $id = $this->get($key);

        $userToken = $this->getOneById($id);

        if(!is_null($userToken)) {
            $keyId = $this->key('id', $userToken->getId());
            $keyToken = $this->key('token', $userToken->getToken());
            $keyUserId = $this->key('userId', $userToken->getUserId());

            $this->purge([$keyId, $keyToken, $keyUserId]);
        }
    }

    public function expireByToken($token)
    {
        $key = $this->key('token', $token);
        $id = $this->get($key);

        $userToken = $this->getOneById($id);

        if(!is_null($userToken)) {
            $keyId = $this->key('id', $userToken->getId());
            $keyToken = $this->key('token', $userToken->getToken());
            $keyUserId = $this->key('userId', $userToken->getUserId());

            $this->purge([$keyId, $keyToken, $keyUserId]);
        }
    }
}
