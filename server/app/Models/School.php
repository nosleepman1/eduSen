<?php

namespace App\Models;

use App\Enums\SubscriptionStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class School extends Model
{
    use HasUuids, SoftDeletes, HasFactory;

    protected $fillable = [
        'owner_id',
        'name',
        'code',
        'ninea',
        'email',
        'is_active',
        'is_school_setup',
        'setup_step',
        'setup_completed_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_school_setup' => 'boolean',
            'setup_completed_at' => 'datetime',
        ];
    }

    // ─── Relations ────────────────────────────────────────────

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function details(): HasOne
    {
        return $this->hasOne(SchoolDetails::class);
    }

    /**
     * All personnel records for this school (replaces the old users() relation).
     */
    public function personnel(): HasMany
    {
        return $this->hasMany(Personnel::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function addresses(): MorphMany
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    public function phones(): MorphMany
    {
        return $this->morphMany(Phone::class, 'phoneable');
    }

    public function rolePermissions(): HasMany
    {
        return $this->hasMany(RolePermission::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->where('status', SubscriptionStatusEnum::Active)
            ->whereDate('ends_at', '>=', now()->toDateString());
    }

    // ─── Scopes ───────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    // ─── Helpers ──────────────────────────────────────────────

    public function isSetupComplete(): bool
    {
        return $this->is_school_setup === true;
    }

    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription()->exists();
    }

    public static function generateCode(): string
    {
        do {
            $code = 'SCH-'.now()->format('Y').'-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        } while (self::query()->where('code', $code)->exists());

        return $code;
    }
}
