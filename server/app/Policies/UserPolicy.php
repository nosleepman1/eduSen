<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function view(User $user, User $model): bool
    {
        return $user->isSuperAdmin() || $user->school_id === $model->school_id;
    }

    public function update(User $user, User $model): bool
    {
        return $user->isSuperAdmin() || $user->id === $model->id;
    }
}
