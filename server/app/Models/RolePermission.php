<?php

namespace App\Models;

use App\Enums\RoleEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class RolePermission extends Model
{
    use HasUuids;

    protected $fillable = [
        'school_id',
        'role',
        'permissions',
    ];

    protected function casts(): array
    {
        return [
            'role' => RoleEnum::class,
            'permissions' => 'array',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Combined permissions for a set of roles in a school.
     *
     * Cached one entry per role rather than one per role combination: a
     * combination key can only be invalidated by scanning, which ties the whole
     * mechanism to Redis. Per-role keys are addressable, so clearCache() works
     * on any cache driver.
     *
     * @param  array<int, RoleEnum|string>  $roles
     * @return list<string>
     */
    public static function getCachedPermissions(string $schoolId, array $roles): array
    {
        $permissions = [];

        foreach (self::normalizeRoles($roles) as $role) {
            $permissions = array_merge($permissions, self::permissionsForRole($schoolId, $role));
        }

        return array_values(array_unique($permissions));
    }

    /**
     * @return list<string>
     */
    private static function permissionsForRole(string $schoolId, string $role): array
    {
        return Cache::remember(
            self::cacheKey($schoolId, $role),
            now()->addDay(),
            fn (): array => self::query()
                ->where('school_id', $schoolId)
                ->where('role', $role)
                ->first()
                ?->permissions ?? []
        );
    }

    public static function cacheKey(string $schoolId, string $role): string
    {
        return "permissions:school:{$schoolId}:role:{$role}";
    }

    /**
     * Drop cached permissions for one role, or for every role of a school.
     */
    public static function clearCache(string $schoolId, RoleEnum|string|null $role = null): void
    {
        $roles = $role !== null
            ? self::normalizeRoles([$role])
            : array_column(RoleEnum::cases(), 'value');

        foreach ($roles as $value) {
            Cache::forget(self::cacheKey($schoolId, $value));
        }
    }

    /**
     * @param  array<int, RoleEnum|string>  $roles
     * @return list<string>
     */
    private static function normalizeRoles(array $roles): array
    {
        return array_values(array_map(
            fn ($role): string => $role instanceof RoleEnum ? $role->value : (string) $role,
            $roles
        ));
    }

    /**
     * Check if a specific permission is granted for this role.
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? [], true);
    }
}
