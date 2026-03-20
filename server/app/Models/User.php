<?php

namespace App\Models;

use App\Enums\GenderEnum;
use App\Enums\RoleEnum;
use App\Enums\UserStatusEnum;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasUuids, Notifiable, SoftDeletes;

    protected $fillable = [
        'firstname',
        'lastname',
        'email',
        'password',
        'phone_number',
        'birth_date',
        'birth_place',
        'gender',
        'status',
        'password_changed_at',
        'last_login_at',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'email_verified_at' => 'datetime',
            'gender' => GenderEnum::class,
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'password_changed_at' => 'datetime',
            'status' => UserStatusEnum::class,
        ];
    }

    // ─── Relations ────────────────────────────────────────────

    /**
     * All personnel records across all schools.
     */
    public function personnelRecords(): HasMany
    {
        return $this->hasMany(Personnel::class);
    }

    /**
     * All roles across all schools.
     */
    public function roles(): HasMany
    {
        return $this->hasMany(UserRole::class);
    }

    /**
     * Schools this user belongs to, via personnel records.
     */
    public function schools(): HasManyThrough
    {
        return $this->hasManyThrough(
            School::class,
            Personnel::class,
            'user_id',     // FK on personnel
            'id',          // FK on schools
            'id',          // LK on users
            'school_id'    // LK on personnel
        );
    }

    /**
     * The school this user owns (if any).
     */
    public function ownedSchool(): HasOne
    {
        return $this->hasOne(School::class, 'owner_id');
    }

    public function phones(): MorphMany
    {
        return $this->morphMany(Phone::class, 'phoneable');
    }

    public function otpCodes(): HasMany
    {
        return $this->hasMany(OtpCode::class, 'phone_number', 'phone_number');
    }

    // ─── Multi-Tenant Helpers ─────────────────────────────────

    /**
     * Get the personnel record for a specific school.
     */
    public function personnelForSchool(string $schoolId): ?Personnel
    {
        return $this->personnelRecords()
            ->where('school_id', $schoolId)
            ->first();
    }

    /**
     * Check if user has a specific role in a specific school.
     */
    public function hasRoleInSchool(RoleEnum $role, string $schoolId): bool
    {
        return $this->roles()
            ->where('school_id', $schoolId)
            ->where('role', $role)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Check if user has any of the given roles in a specific school.
     */
    public function hasAnyRoleInSchool(array $roles, string $schoolId): bool
    {
        return $this->roles()
            ->where('school_id', $schoolId)
            ->whereIn('role', $roles)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Get user's active roles for a specific school.
     */
    public function rolesForSchool(string $schoolId)
    {
        return $this->roles()
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->get();
    }

    // ─── Global Role Checks ──────────────────────────────────

    /**
     * Check if user is a SuperAdmin (platform-level, no school).
     */
    public function isSuperAdmin(): bool
    {
        return $this->roles()
            ->whereNull('school_id')
            ->where('role', RoleEnum::SuperAdmin)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Check if user is an Owner for a specific school.
     */
    public function isOwnerOf(string $schoolId): bool
    {
        return $this->hasRoleInSchool(RoleEnum::Owner, $schoolId);
    }

    // ─── Status Helpers ──────────────────────────────────────

    public function mustChangePassword(): bool
    {
        return $this->password_changed_at === null;
    }

    public function isActive(): bool
    {
        return $this->status === UserStatusEnum::Active;
    }

    // ─── Scopes ──────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', UserStatusEnum::Active);
    }

    // ─── Accessors ───────────────────────────────────────────

    public function getFullNameAttribute(): string
    {
        return trim($this->firstname . ' ' . $this->lastname);
    }
}
