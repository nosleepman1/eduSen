<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Curriculum extends Model
{
    use \App\Traits\Auditable, HasFactory, HasUuids, SoftDeletes;

    protected $table = 'curriculums';

    protected $fillable = [
        'school_id',
        'academic_year_id',
        'level_id',
        'specialization_id',
        'subject_id',
        'coefficient',
        'weekly_hours',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'coefficient' => 'decimal:2',
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

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    // ─── Scopes ───────────────────────────────────────────────

    public function scopeBySchool(Builder $query, string $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }
}
