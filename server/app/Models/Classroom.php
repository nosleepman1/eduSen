<?php

namespace App\Models;

use App\Enums\ClassroomStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Classroom extends Model
{
    use \App\Traits\Auditable, HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'school_id',
        'academic_year_id',
        'level_id',
        'specialization_id',
        'name',
        'code',
        'capacity',
        'main_teacher_id',
        'main_room',
        'status',
        'description',
        'end_date_override',
    ];

    protected function casts(): array
    {
        return [
            'end_date_override' => 'date',
            'status' => ClassroomStatusEnum::class,
        ];
    }

    // ─── Relations ────────────────────────────────────────────

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function specialization(): BelongsTo
    {
        return $this->belongsTo(Specialization::class);
    }

    public function mainTeacher(): BelongsTo
    {
        return $this->belongsTo(Personnel::class, 'main_teacher_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function timetables(): HasMany
    {
        return $this->hasMany(Timetable::class);
    }

    // ─── Scopes ───────────────────────────────────────────────

    public function scopeBySchool(Builder $query, string $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }

    // ─── Helpers ──────────────────────────────────────────────

    public function isDraft(): bool
    {
        return $this->status === ClassroomStatusEnum::Draft;
    }

    public function isActive(): bool
    {
        return $this->status === ClassroomStatusEnum::Active;
    }

    public function isClosed(): bool
    {
        return $this->status === ClassroomStatusEnum::Closed;
    }

    public function getFullNameAttribute(): string
    {
        $segments = array_filter([
            $this->level?->name,
            $this->specialization?->code,
            $this->name,
        ], fn ($segment) => $segment !== null && $segment !== '');

        return implode(' ', $segments);
    }
}
