<?php

namespace App\Models;

use App\Enums\SubscriptionStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use HasUuids, \App\Traits\Auditable;

    protected $fillable = [
        'school_id',
        'subscription_plan_id',
        'billing_cycle_id',
        'status',
        'starts_at',
        'ends_at',
        'grace_ends_at',
        'auto_renew',
        'cancelled_at',
        'cancellation_reason',
        'plan_name_snapshot',
        'plan_price_snapshot',
        'billing_cycle_snapshot',
        'discount_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatusEnum::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'grace_ends_at' => 'datetime',
            'auto_renew' => 'boolean',
            'cancelled_at' => 'datetime',
            'plan_price_snapshot' => 'decimal:2',
            'discount_snapshot' => 'decimal:2',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function billingCycle(): BelongsTo
    {
        return $this->belongsTo(BillingCycle::class);
    }

    // Query Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereIn('status', [SubscriptionStatusEnum::Active, SubscriptionStatusEnum::Trial])
                ->where('ends_at', '>=', now());
        })->orWhere(function (Builder $q) {
            $q->where('status', SubscriptionStatusEnum::GracePeriod)
                ->where('grace_ends_at', '>=', now());
        });
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereIn('status', [SubscriptionStatusEnum::Expired, SubscriptionStatusEnum::TrialExpired])
            ->orWhere(function (Builder $q) {
                $q->where('status', '!=', SubscriptionStatusEnum::Suspended)
                    ->where('ends_at', '<', now())
                    ->where(function (Builder $sub) {
                        $sub->whereNull('grace_ends_at')
                            ->orWhere('grace_ends_at', '<', now());
                    });
            });
    }

    public function scopeTrial(Builder $query): Builder
    {
        return $query->where('status', SubscriptionStatusEnum::Trial);
    }

    public function scopeSuspended(Builder $query): Builder
    {
        return $query->where('status', SubscriptionStatusEnum::Suspended);
    }

    // Helpers
    public function isActivePeriod(): bool
    {
        if (in_array($this->status, [SubscriptionStatusEnum::Active, SubscriptionStatusEnum::Trial])) {
            return $this->ends_at->isFuture();
        }

        if ($this->status === SubscriptionStatusEnum::GracePeriod) {
            return $this->grace_ends_at && $this->grace_ends_at->isFuture();
        }

        return false;
    }

    public function isInGracePeriod(): bool
    {
        return $this->status === SubscriptionStatusEnum::GracePeriod && $this->grace_ends_at && $this->grace_ends_at->isFuture();
    }
}
