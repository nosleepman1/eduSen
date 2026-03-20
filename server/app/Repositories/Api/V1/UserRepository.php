<?php

namespace App\Repositories\Api\V1;

use App\Models\User;

class UserRepository
{
    public function create(array $data): User
    {
        return User::query()->create($data);
    }

    public function update(User $user, array $data): User
    {
        $user->update($data);

        return $user->refresh();
    }
}
