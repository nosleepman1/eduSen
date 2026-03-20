<?php

namespace App\Models;

use App\Enums\LessonSessionStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LessonSession extends Model
{
    use \App\Traits\Auditable, HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'school_id',
        'schedule_entry_id',
        'session_date',
        'scheduled_start_time',
        'scheduled_end_time',
        'actual_start',
        'actual_end',
        'status',
        'cancellation_reason',
        'admin_comment',
    ];

    protected function casts(): array
    {
        return [
            'session_date' => 'date',
            'actual_start' => 'datetime',
            'actual_end' => 'datetime',
            'status' => LessonSessionStatusEnum::class,
        ];
    }

    // ─── Relations ────────────────────────────────────────────

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function scheduleEntry(): BelongsTo
    {
        return $this->belongsTo(ScheduleEntry::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function lessonContents(): HasMany
    {
        return $this->hasMany(LessonContent::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }

    // ─── Scopes ───────────────────────────────────────────────

    public function scopeBySchool(Builder $query, string $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('session_date', now()->toDateString());
    }

    // ─── Helpers ──────────────────────────────────────────────

    public function isArchived(): bool
    {
        return $this->status === LessonSessionStatusEnum::Archived;
    }

    public function scheduledStartsAt(): \Carbon\Carbon
    {
        return \Carbon\Carbon::parse($this->session_date->toDateString() . ' ' . $this->scheduled_start_time);
    }

    public function scheduledEndsAt(): \Carbon\Carbon
    {
        return \Carbon\Carbon::parse($this->session_date->toDateString() . ' ' . $this->scheduled_end_time);
    }
}
