<?php

namespace App\Models;

use App\Enums\RelationshipEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuardianStudent extends Model
{
    use HasUuids;

    protected $table = 'guardian_student';

    public $timestamps = false;

    protected $fillable = [
        'guardian_id',
        'student_id',
        'relationship',
        'is_primary_contact',
    ];

    protected function casts(): array
    {
        return [
            'relationship' => RelationshipEnum::class,
            'is_primary_contact' => 'boolean',
        ];
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
