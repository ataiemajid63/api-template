<?php

namespace App\Http\Resources\V1;

use App\Entities\User;
use App\Http\Resources\IResource;
use App\Http\Resources\Resource;

class UserCompactResource extends Resource implements IResource
{
    private $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function toArray(): array
    {
        $entity = [
            'name' => $this->user->fullName(),
        ];

        return $entity;
    }
}
