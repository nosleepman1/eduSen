<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanFeature extends Model
{
    use HasUuids;

    protected $table = 'plan_features';

    protected $fillable = [
        'subscription_plan_id',
        'feature_id',
        'limit_value',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'limit_value' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class);
    }
}
