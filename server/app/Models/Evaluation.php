<?php

namespace App\Models;

use App\Enums\EvaluationStatusEnum;
use App\Enums\EvaluationTypeEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Evaluation extends Model
{
    use \App\Traits\Auditable, HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'school_id',
        'academic_year_id',
        'academic_period_id',
        'teaching_assignment_id',
        'lesson_session_id',
        'type',
        'title',
        'description',
        'evaluation_date',
        'max_score',
        'coefficient',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'evaluation_date' => 'date',
            'max_score' => 'decimal:2',
            'coefficient' => 'decimal:2',
            'type' => EvaluationTypeEnum::class,
            'status' => EvaluationStatusEnum::class,
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

    public function academicPeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    public function teachingAssignment(): BelongsTo
    {
        return $this->belongsTo(TeachingAssignment::class);
    }

    public function lessonSession(): BelongsTo
    {
        return $this->belongsTo(LessonSession::class);
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }

    // ─── Scopes ───────────────────────────────────────────────

    public function scopeBySchool(Builder $query, string $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }

    // ─── Helpers ──────────────────────────────────────────────

    public function isLocked(): bool
    {
        return $this->status === EvaluationStatusEnum::Locked;
    }

    public function isDraft(): bool
    {
        return $this->status === EvaluationStatusEnum::Draft;
    }

    public function isOpen(): bool
    {
        return $this->status === EvaluationStatusEnum::Open;
    }

    public function isClosed(): bool
    {
        return $this->status === EvaluationStatusEnum::Closed;
    }
}
