<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Teacher extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'personnel_id',
        'speciality',
        'diploma',
        'license_number',
        'years_experience',
    ];

    // ─── Relations ────────────────────────────────────────────

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class);
    }

    public function teachingAssignments(): HasMany
    {
        return $this->hasMany(TeachingAssignment::class);
    }

    // ─── Delegated Accessors ─────────────────────────────────

    public function getFullNameAttribute(): string
    {
        return $this->personnel?->full_name ?? '';
    }

    public function getSchoolAttribute(): ?School
    {
        return $this->personnel?->school;
    }

    public function getUserAttribute(): ?User
    {
        return $this->personnel?->user;
    }
}
