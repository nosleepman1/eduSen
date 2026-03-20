<?php

namespace App\Models;

use App\Enums\DayOfWeekEnum;
use App\Enums\ScheduleEntryFrequencyEnum;
use App\Enums\ScheduleEntryStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScheduleEntry extends Model
{
    use \App\Traits\Auditable, HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'school_id',
        'timetable_id',
        'teaching_assignment_id',
        'day_of_week',
        'start_time',
        'end_time',
        'room',
        'frequency',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => DayOfWeekEnum::class,
            'frequency' => ScheduleEntryFrequencyEnum::class,
            'status' => ScheduleEntryStatusEnum::class,
        ];
    }

    // ─── Relations ────────────────────────────────────────────

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function timetable(): BelongsTo
    {
        return $this->belongsTo(Timetable::class);
    }

    public function teachingAssignment(): BelongsTo
    {
        return $this->belongsTo(TeachingAssignment::class);
    }

    public function lessonSessions(): HasMany
    {
        return $this->hasMany(LessonSession::class);
    }

    // ─── Scopes ───────────────────────────────────────────────

    public function scopeBySchool(Builder $query, string $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ScheduleEntryStatusEnum::Active);
    }

    // ─── Helpers ──────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === ScheduleEntryStatusEnum::Active;
    }

    /**
     * Duration is never stored (spec: "La durée n'est jamais enregistrée"),
     * always derived from start_time/end_time.
     */
    public function getDurationMinutesAttribute(): int
    {
        return abs(\Carbon\Carbon::parse($this->start_time)->diffInMinutes(\Carbon\Carbon::parse($this->end_time)));
    }
}
