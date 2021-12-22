<?php

namespace App\Repositories\Contracts;

use App\Entities\User;
use Illuminate\Support\Collection;

interface IUserRepository
{
    /**
     * @param User $user
     *
     * @return User|null
     */
    public function insert(User $user): ?User;

    /**
     * @param User $user
     *
     * @return User|null
     */
    public function update(User $user): ?User;

    /**
     * @param int $id
     *
     * @return bool
     */
    public function delete($id);

    /**
     * @param int[] $ids
     *
     * @return Collection|User[]
     */
    public function getAllByIds(array $ids): Collection;

    /**
     * @param int $id
     *
     * @return User|null
     */
    public function getOneById($id): ?User;

    /**
     * @param int $mobile
     *
     * @return User|null
     */
    public function getOneByMobile($mobile): ?User;

    /**
     * @param int $email
     *
     * @return User|null
     */
    public function getOneByEmail($email): ?User;
}
