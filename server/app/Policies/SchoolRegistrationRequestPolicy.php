<?php

namespace App\Policies;

use App\Models\SchoolRegistrationRequest;
use App\Models\User;

class SchoolRegistrationRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function view(User $user, SchoolRegistrationRequest $request): bool
    {
        return $user->isSuperAdmin() || $user->email === $request->email;
    }

    public function update(User $user, SchoolRegistrationRequest $request): bool
    {
        return $user->isSuperAdmin();
    }
}
