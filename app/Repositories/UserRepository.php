<?php

namespace App\Repositories;

use App\Entities\User;
use App\Repositories\Contracts\IUserRepository;
use App\Repositories\Mysql\UserRepository as UserMysqlRepository;
use App\Repositories\Redis\UserRepository as UserRedisRepository;
use Illuminate\Support\Collection;

class UserRepository extends Repository implements IUserRepository
{
    private $userMysqlRepository;
    private $userRedisRepository;

    public function __construct(UserMysqlRepository $userMysqlRepository, UserRedisRepository $userRedisRepository)
    {
        parent::__construct();

        $this->userMysqlRepository = $userMysqlRepository;
        $this->userRedisRepository = $userRedisRepository;
    }

    public function insert(User $user): ?User
    {
        $user = $this->userMysqlRepository->insert($user);

        if (!is_null($user->getId())) {
            $this->userRedisRepository->insert($user);
        }

        return $user;
    }

    public function update(User $user): ?User
    {
        $this->userMysqlRepository->update($user);
        $this->userRedisRepository->update($user);

        return $user;
    }

    public function delete($id)
    {
        $this->userMysqlRepository->delete($id);
        $this->userRedisRepository->delete($id);
    }

    public function purge($id)
    {
        $this->userRedisRepository->delete($id);
    }

    public function getAllByIds(array $ids): Collection
    {
        $users = $this->userRedisRepository->getAllByIds($ids);

        if ($users->isEmpty()) {
            $users = $this->userMysqlRepository->getAllByIds($ids);

            if ($users->isNotEmpty()) {
                $this->userRedisRepository->bulkInsert($users);
            }
        }

        return $users;
    }

    public function getOneById($id): ?User
    {
        $user = $this->userRedisRepository->getOneById($id);

        if (is_null($user)) {
            $user = $this->userMysqlRepository->getOneById($id);

            if (!is_null($user)) {
                $this->userRedisRepository->insert($user);
            }
        }

        return $user;
    }

    public function getOneByMobile($mobile): ?User
    {
        $user = $this->userRedisRepository->getOneByMobile($mobile);

        if (is_null($user)) {
            $user = $this->userMysqlRepository->getOneByMobile($mobile);

            if (!is_null($user)) {
                $this->userRedisRepository->insert($user);
            }
        }

        return $user;
    }

    public function getOneByEmail($email): ?User
    {
        $user = $this->userRedisRepository->getOneByEmail($email);

        if (is_null($user)) {
            $user = $this->userMysqlRepository->getOneByEmail($email);

            if (!is_null($user)) {
                $this->userRedisRepository->insert($user);
            }
        }

        return $user;
    }

    public function purgeCacheByUserId($userId)
    {
        $this->userRedisRepository->purgeCacheByUserId($userId);
    }
}
