<?php

namespace App\Models;

use App\Enums\RoleEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserRole extends Model
{
    use HasUuids, \App\Traits\Auditable;

    protected $fillable = [
        'user_id',
        'school_id',
        'role',
        'assigned_by',
        'assigned_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'is_active' => 'boolean',
            'role' => RoleEnum::class,
        ];
    }

    // ─── Relations ────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    // ─── Helpers ──────────────────────────────────────────────

    public function isOwner(): bool
    {
        return $this->role === RoleEnum::Owner;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === RoleEnum::SuperAdmin && $this->school_id === null;
    }
}
