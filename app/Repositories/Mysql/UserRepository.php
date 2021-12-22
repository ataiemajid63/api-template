<?php

namespace App\Repositories\Mysql;

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
    }

    public function insert(User $user): ?User
    {
        $query = $this->query();

        $id = $query->insertGetId([
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'email' => $user->getEmail(),
            'mobile' => $user->getMobile(),
            'password' => $user->getPassword(),
            'national_code' => $user->getNationalCode(),
            'chat_id' => $user->getChatId(),
            'status' => $user->getStatus(),
            'created_at' => $user->getCreatedAt(),
            'updated_at' => $user->getUpdatedAt(),
        ]);

        $user->setId($id);

        return $user;
    }

    public function update(User $user): ?User
    {
        $query = $this->query();

        $query->where('id', $user->getId());
        $query->update([
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'email' => $user->getEmail(),
            'password' => $user->getPassword(),
            'mobile' => $user->getMobile(),
            'national_code' => $user->getNationalCode(),
            'chat_id' => $user->getChatId(),
            'status' => $user->getStatus(),
            'updated_at' => $user->getUpdatedAt(),
        ]);

        return $user;
    }

    public function delete($id)
    {
        $query = $this->query();

        $query->where('id', $id);
        $res = $query->update([
            'deleted_at' => time()
        ]);

        return (bool)$res;
    }

    public function getAllByIds(array $ids): Collection
    {
        $query = $this->query();

        $query->whereIn('id', $ids);

        $entities = $query->get();

        $users = (new UserFactory())->makeFromCollection($entities);

        return $users;
    }

    public function getOneById($id): ?User
    {
        $query = $this->query();

        $query->where('id', $id);

        $entity = $query->first();

        $user = $entity ? (new UserFactory())->make($entity) : null;

        return $user;
    }

    public function getOneByMobile($mobile): ?User
    {
        $query = $this->query();

        $query->where('mobile', $mobile);

        $entity = $query->first();

        $user = $entity ? (new UserFactory())->make($entity) : null;

        return $user;
    }

    public function getOneByEmail($email): ?User
    {
        $query = $this->query();

        $query->where('email', $email);

        $entity = $query->first();

        $user = $entity ? (new UserFactory())->make($entity) : null;

        return $user;
    }
}
