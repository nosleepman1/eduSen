<?php

namespace App\Models;

use App\Enums\GenderEnum;
use App\Enums\StudentStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasFactory, HasUuids, SoftDeletes, \App\Traits\Auditable;

    protected $fillable = [
        'school_id',
        'user_id',
        'student_number',
        'firstname',
        'lastname',
        'birth_date',
        'birth_place',
        'gender',
        'nationality',
        'photo',
        'blood_group',
        'medical_info',
        'address',
        'status',
        'enrolled_at',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'enrolled_at' => 'date',
            'gender' => GenderEnum::class,
            'status' => StudentStatusEnum::class,
        ];
    }

    // ─── Relations ────────────────────────────────────────────

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(Guardian::class, 'guardian_student')
            ->withPivot(['relationship', 'is_primary_contact']);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    // ─── Scopes ───────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', StudentStatusEnum::Active);
    }

    public function scopeBySchool(Builder $query, string $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }

    // ─── Helpers ──────────────────────────────────────────────

    public function getFullNameAttribute(): string
    {
        return trim($this->firstname . ' ' . $this->lastname);
    }

    public function isActive(): bool
    {
        return $this->status === StudentStatusEnum::Active;
    }

    /**
     * Get the primary contact guardian for this student.
     */
    public function primaryContact(): ?Guardian
    {
        return $this->guardians()
            ->wherePivot('is_primary_contact', true)
            ->first();
    }
}
