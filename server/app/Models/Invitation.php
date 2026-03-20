<?php

namespace App\Models;

use App\Enums\RoleEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invitation extends Model
{
    use HasUuids, \App\Traits\Auditable;

    protected $fillable = [
        'school_id',
        'email',
        'firstname',
        'lastname',
        'role',
        'token',
        'status',
        'expires_at',
        'accepted_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'role' => RoleEnum::class,
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    // ─── Relations ────────────────────────────────────────────

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ─── Helpers ──────────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === 'PENDING';
    }

    public function isExpired(): bool
    {
        return $this->status === 'EXPIRED' || $this->expires_at->isPast();
    }

    public function isAccepted(): bool
    {
        return $this->status === 'ACCEPTED';
    }

    public function markAccepted(): void
    {
        $this->forceFill([
            'status' => 'ACCEPTED',
            'accepted_at' => now(),
        ])->save();
    }

    public function markExpired(): void
    {
        $this->forceFill(['status' => 'EXPIRED'])->save();
    }

    public function markCancelled(): void
    {
        $this->forceFill(['status' => 'CANCELLED'])->save();
    }
}
