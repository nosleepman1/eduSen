<?php

namespace App\Models;

use App\Enums\PhoneTypeEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Phone extends Model
{
    use HasUuids;

    protected $fillable = [
        'phoneable_id',
        'phoneable_type',
        'phone_number',
        'type',
        'country_code',
        'dial_code',
        'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'type' => PhoneTypeEnum::class,
        ];
    }

    public function phoneable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopePrimary(Builder $query): Builder
    {
        return $query->where('is_primary', true);
    }

    public function scopeByType(Builder $query, PhoneTypeEnum $type): Builder
    {
        return $query->where('type', $type);
    }
}
