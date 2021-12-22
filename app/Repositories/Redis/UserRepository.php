<?php

namespace App\Repositories\Redis;

use App\Entities\User;
use App\Factories\UserFactory;
use App\Repositories\Contracts\IUserRepository;
use Illuminate\Support\Collection;

class UserRepository extends Repository implements IUserRepository
{
    public function __construct()
    {
        parent::__construct();

        $this->table = 'users';
        $this->ttl = 24 * 60 * 60; // 1 Day
    }

    public function insert(User $user): ?User
    {
        $key = $this->key('id', $user->getId());
        $keyMobile = $this->key('mobile', $user->getMobile());
        $keyEmail = $this->key('email', $user->getEmail());
        $value = $user->toArray(true);

        $this->set($key, $value, $this->ttl);
        $this->set($keyMobile, $user->getId(), $this->ttl);
        $this->set($keyEmail, $user->getId(), $this->ttl);

        return $user;
    }

    public function bulkInsert(Collection $users)
    {
        $entities = [];

        foreach ($users as $user) {
            $key = $this->key('id', $user->getId());
            $keyMobile = $this->key('mobile', $user->getMobile());
            $keyEmail = $this->key('email', $user->getEmail());

            $entities[$key] = $user->toArray(true);
            $entities[$keyMobile] = $user->getId();
            $entities[$keyEmail] = $user->getId();
        }

        $this->setMultiple($entities, $this->ttl);
    }

    public function update(User $user): ?User
    {
        $key = $this->key('id', $user->getId());
        $keyMobile = $this->key('mobile', $user->getMobile());
        $keyEmail = $this->key('email', $user->getEmail());
        $value = $user->toArray(true);

        $this->set($key, $value, $this->ttl);
        $this->set($keyMobile, $user->getId(), $this->ttl);
        $this->set($keyEmail, $user->getId(), $this->ttl);

        return $user;
    }

    public function delete($id)
    {
        $key = $this->key('id', $id);

        $this->purge($key);
    }

    public function getAllByIds(array $ids): Collection
    {
        $users = collect();
        $keys = [];

        foreach ($ids as $id) {
            $keys[] = $this->key('id', $id);
        }

        if (!empty($keys)) {
            $entities = $this->getMultiple($keys);

            $users = (new UserFactory())->makeFromArray($entities);
        }

        if (count($ids) !== $users->count()) {
            $users = collect();
        }

        return $users;
    }

    public function getOneById($id): ?User
    {
        $key = $this->key('id', $id);

        $entity = $this->get($key);

        $user = $entity ? (new UserFactory())->makeWithArray((array)$entity) : null;

        return $user;
    }

    public function getOneByMobile($mobile): ?User
    {
        $key = $this->key('mobile', $mobile);

        $id = $this->get($key);

        return $this->getOneById($id);
    }

    public function getOneByEmail($email): ?User
    {
        $key = $this->key('email', $email);

        $id = $this->get($key);

        return $this->getOneById($id);
    }

    public function purgeCacheByUserId($userId)
    {
        $key = $this->key('id', $userId);

        $this->purge($key);
    }
}
