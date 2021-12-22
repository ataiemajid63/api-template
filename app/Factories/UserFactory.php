<?php

namespace App\Factories;

use App\Entities\User;
use App\Enums\UserStatus;

class UserFactory extends Factory
{
    public function __construct()
    {
        parent::__construct();
    }

    public function make(\stdClass $entity): User
    {
        $user = new User();

        $user->setId($entity->id ?? null);
        $user->setFirstName($entity->first_name ?? null);
        $user->setLastName($entity->last_name ?? null);
        $user->setEmail($entity->email ?? null);
        $user->setPassword($entity->password ?? null);
        $user->setMobile($entity->mobile ?? null);
        $user->setNationalCode($entity->national_code ?? null);
        $user->setChatId(null);
        $user->setStatus($entity->status ?? UserStatus::CREATED);
        $user->setCreatedAt($entity->created_at ?? null);
        $user->setUpdatedAt($entity->updated_at ?? null);
        $user->setDeletedAt($entity->deleted_at ?? null);

        return $user;
    }
}
