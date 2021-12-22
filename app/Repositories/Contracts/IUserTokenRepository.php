<?php

namespace App\Repositories\Contracts;

use App\Entities\UserToken;

interface IUserTokenRepository
{
    /**
     * @param UserToken $userToken
     *
     * @return UserToken|null
     */
    public function insert(UserToken $userToken): ?UserToken;

    /**
     * @param string $token
     *
     * @return UserToken|null
     */
    public function getOneByToken($token): ?UserToken;

    /**
     * @param int $userId
     *
     * @return void
     */
    public function expireByUserId($userId);

    /**
     * @param string $token
     *
     * @return void
     */
    public function expireByToken($token);


}
