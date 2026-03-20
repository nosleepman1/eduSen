<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BulletinLine extends Model
{
    use \App\Traits\Auditable, HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'school_id',
        'bulletin_id',
        'curriculum_id',
        'subject_average',
        'coefficient',
        'points',
        'teacher_comment',
    ];

    protected function casts(): array
    {
        return [
            'subject_average' => 'decimal:2',
            'coefficient' => 'decimal:2',
            'points' => 'decimal:2',
        ];
    }

    // ─── Relations ────────────────────────────────────────────

    public function bulletin(): BelongsTo
    {
        return $this->belongsTo(Bulletin::class);
    }

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class);
    }
}
