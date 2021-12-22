<?php

namespace App\Repositories;

use App\Entities\UserToken;
use App\Repositories\Contracts\IUserTokenRepository;
use App\Repositories\Mysql\UserTokenRepository as UserTokenMysqlRepository;
use App\Repositories\Redis\UserTokenRepository as UserTokenRedisRepository;

class UserTokenRepository extends Repository implements IUserTokenRepository
{
    private $userTokenMysqlRepository;
    private $userTokenRedisRepository;

    public function __construct(UserTokenMysqlRepository $userTokenMysqlRepository, UserTokenRedisRepository $userTokenRedisRepository)
    {
        parent::__construct();

        $this->userTokenMysqlRepository = $userTokenMysqlRepository;
        $this->userTokenRedisRepository = $userTokenRedisRepository;
    }

    public function insert(UserToken $userToken): ?UserToken
    {
        $userToken = $this->userTokenMysqlRepository->insert($userToken);

        if(!is_null($userToken->getId())) {
            $this->userTokenRedisRepository->insert($userToken);
        }

        return $userToken;
    }

    public function getOneByToken($token): ?UserToken
    {
        $userToken = $this->userTokenRedisRepository->getOneByToken($token);

        if(is_null($userToken)) {
            $userToken = $this->userTokenMysqlRepository->getOneByToken($token);

            if(!is_null($userToken)) {
                $this->userTokenRedisRepository->insert($userToken);
            }
        }

        return $userToken;
    }

    public function expireByUserId($userId)
    {
        $this->userTokenMysqlRepository->expireByUserId($userId);
        $this->userTokenRedisRepository->expireByUserId($userId);
    }

    public function expireByToken($token)
    {
        $this->userTokenMysqlRepository->expireByToken($token);
        $this->userTokenRedisRepository->expireByToken($token);
    }
}
