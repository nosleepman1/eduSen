<?php

namespace App\Models;

use App\Enums\LessonContentStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LessonContent extends Model
{
    use \App\Traits\Auditable, HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'school_id',
        'lesson_session_id',
        'title',
        'description',
        'progress_number',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => LessonContentStatusEnum::class,
        ];
    }

    // ─── Relations ────────────────────────────────────────────

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function lessonSession(): BelongsTo
    {
        return $this->belongsTo(LessonSession::class);
    }

    // ─── Scopes ───────────────────────────────────────────────

    public function scopeBySchool(Builder $query, string $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }
}
