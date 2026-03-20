<?php

namespace App\Models;

use App\Enums\BulletinStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bulletin extends Model
{
    use \App\Traits\Auditable, HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'school_id',
        'academic_period_id',
        'enrollment_id',
        'general_average',
        'class_rank',
        'total_students',
        'mention',
        'class_council_comment',
        'status',
        'generated_at',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'general_average' => 'decimal:2',
            'status' => BulletinStatusEnum::class,
            'generated_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    // ─── Relations ────────────────────────────────────────────

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function academicPeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(BulletinLine::class);
    }

    // ─── Scopes ───────────────────────────────────────────────

    public function scopeBySchool(Builder $query, string $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }

    // ─── Helpers ──────────────────────────────────────────────

    public function isLocked(): bool
    {
        return $this->status === BulletinStatusEnum::Locked;
    }

    public function isDraft(): bool
    {
        return $this->status === BulletinStatusEnum::Draft;
    }

    public function isValidated(): bool
    {
        return $this->status === BulletinStatusEnum::Validated;
    }

    public function isPublished(): bool
    {
        return $this->status === BulletinStatusEnum::Published;
    }
}
