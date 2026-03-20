<?php

namespace App\Http\Resources\School;

use App\Enums\RoleEnum;
use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PersonnelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'registration_number' => $this->registration_number,
            'full_name' => $this->full_name,
            'email' => $this->user?->email,
            'department' => $this->department,
            'position' => $this->position,
            'hire_date' => $this->hire_date?->toDateString(),
            'status' => $this->status?->value,
            // Queried directly rather than via Personnel::roles() (typed HasMany
            // but re-fetches ->user()->first() instead of the already-loaded
            // relation, and can violate its own return type on a null user).
            // PATCH .../roles writes UserRole; without this, a client managing
            // roles has no way to know which ones are already granted.
            'roles' => $this->activeRoleValues(),
        ];
    }

    /**
     * @return list<string>
     */
    private function activeRoleValues(): array
    {
        return UserRole::query()
            ->where('user_id', $this->user_id)
            ->where('school_id', $this->school_id)
            ->where('is_active', true)
            ->pluck('role')
            ->map(fn ($role) => $role instanceof RoleEnum ? $role->value : (string) $role)
            ->values()
            ->all();
    }
}
