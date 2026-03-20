<?php

namespace App\Models;

use App\Enums\FeatureTypeEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Feature extends Model
{
    use HasUuids;

    protected $fillable = [
        'code',
        'name',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'type' => FeatureTypeEnum::class,
        ];
    }

    public function planFeatures(): HasMany
    {
        return $this->hasMany(PlanFeature::class);
    }

    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(SubscriptionPlan::class, 'plan_features')
            ->withPivot(['limit_value', 'active'])
            ->withTimestamps();
    }
}
