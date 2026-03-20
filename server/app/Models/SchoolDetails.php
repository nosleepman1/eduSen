<?php

namespace App\Models;

use App\Enums\SchoolEnum;
use App\Enums\SectorEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolDetails extends Model
{
    use HasUuids;

    protected $table = 'school_details';

    protected $fillable = [
        'school_id',
        'logo',
        'primary_color',
        'secondary_color',
        'slogan',
        'website',
        'school_type',
        'sector',
        'registration_number',
        'founded_at',
        'max_students',
        'max_teachers',
    ];

    protected function casts(): array
    {
        return [
            'founded_at' => 'date',
            'school_type' => SchoolEnum::class,
            'sector' => SectorEnum::class,
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
