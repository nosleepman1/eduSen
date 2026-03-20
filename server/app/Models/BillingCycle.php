<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillingCycle extends Model
{
    use HasUuids;

    protected $fillable = [
        'code',
        'name',
        'duration_days',
        'discount_percentage',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'duration_days' => 'integer',
            'discount_percentage' => 'decimal:2',
            'active' => 'boolean',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
