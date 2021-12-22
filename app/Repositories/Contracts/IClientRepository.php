<?php

namespace App\Repositories\Contracts;

interface IClientRepository
{
    public function getOneByToken($token, $withTrashed = false);
}
