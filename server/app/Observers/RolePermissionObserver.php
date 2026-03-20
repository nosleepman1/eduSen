<?php

namespace App\Observers;

use App\Models\RolePermission;

class RolePermissionObserver
{
    public function saved(RolePermission $rolePermission): void
    {
        RolePermission::clearCache($rolePermission->school_id, $rolePermission->role);
    }

    public function deleted(RolePermission $rolePermission): void
    {
        RolePermission::clearCache($rolePermission->school_id, $rolePermission->role);
    }
}
