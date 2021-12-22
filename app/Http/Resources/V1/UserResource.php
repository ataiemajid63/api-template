<?php

namespace App\Http\Resources\V1;

use App\Entities\User;
use App\Http\Resources\IResource;
use App\Http\Resources\Resource;

class UserResource extends Resource implements IResource
{
    private $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function toArray(): array
    {
        $entity = [
            'id' => $this->user->getId(),
            'first_name' => $this->user->getFirstName(),
            'last_name' => $this->user->getLastName(),
            'email' => $this->user->getEmail(),
            'mobile' => $this->user->getMobile(),
            'national_code' => $this->user->getNationalCode(),
            'status' => $this->user->getStatus(),
            'updated_at' => $this->user->getUpdatedAt(),
        ];

        return $entity;
    }
}
