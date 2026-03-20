<?php

namespace App\Models;

use App\Enums\GradeStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Grade extends Model
{
    use \App\Traits\Auditable, HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'school_id',
        'evaluation_id',
        'enrollment_id',
        'value',
        'status',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'status' => GradeStatusEnum::class,
        ];
    }

    // ─── Relations ────────────────────────────────────────────

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    // ─── Scopes ───────────────────────────────────────────────

    public function scopeBySchool(Builder $query, string $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }

    /**
     * RG-009/010: averages include Graded and Absent grades (Absent counts
     * as 0), but never Pending (not yet entered) or Dispensed (excluded by
     * rule).
     */
    public function scopeCountsTowardAverage(Builder $query): Builder
    {
        return $query->whereIn('status', [GradeStatusEnum::Graded, GradeStatusEnum::Absent]);
    }
}
