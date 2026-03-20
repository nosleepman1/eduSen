<?php

namespace App\Models;

use App\Enums\RegistrationRequestStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;

class SchoolRegistrationRequest extends Model
{
    use HasUuids, Notifiable;

    protected $fillable = [
        'firstname',
        'lastname',
        'email',
        'phone',
        'school_name',
        'school_email',
        'school_phone',
        'ninea',
        'message',
        'status',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
            'status' => RegistrationRequestStatusEnum::class,
        ];
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', RegistrationRequestStatusEnum::Pending);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', RegistrationRequestStatusEnum::Approved);
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', RegistrationRequestStatusEnum::Rejected);
    }

    public function isPending(): bool
    {
        return $this->status === RegistrationRequestStatusEnum::Pending;
    }

    public function approve(User $reviewer): void
    {
        $this->forceFill([
            'status' => RegistrationRequestStatusEnum::Approved,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ])->save();
    }

    public function reject(User $reviewer, string $reason): void
    {
        $this->forceFill([
            'status' => RegistrationRequestStatusEnum::Rejected,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ])->save();
    }
}
