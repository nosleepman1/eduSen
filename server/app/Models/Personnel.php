<?php

namespace App\Models;

use App\Enums\PersonnelStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Personnel extends Model
{
    use HasFactory, HasUuids, SoftDeletes, \App\Traits\Auditable;

    protected $table = 'personnel';

    protected $fillable = [
        'school_id',
        'user_id',
        'registration_number',
        'hire_date',
        'department',
        'position',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'hire_date' => 'date',
            'status' => PersonnelStatusEnum::class,
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

    public function teacher(): HasOne
    {
        return $this->hasOne(Teacher::class);
    }

    public function roles(): HasMany
    {
        return $this->user()
            ->first()
            ?->roles()
            ?->where('school_id', $this->school_id) ?? collect();
    }

    // ─── Scopes ───────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', PersonnelStatusEnum::Active);
    }

    public function scopeBySchool(Builder $query, string $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }

    // ─── Helpers ──────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === PersonnelStatusEnum::Active;
    }

    public function getFullNameAttribute(): string
    {
        return $this->user?->full_name ?? '';
    }
}
