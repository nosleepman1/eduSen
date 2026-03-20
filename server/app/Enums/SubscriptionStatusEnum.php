<?php

namespace App\Enums;

enum SubscriptionStatusEnum: string
{
    case Trial = 'TRIAL';
    case TrialExpired = 'TRIAL_EXPIRED';
    case Active = 'ACTIVE';
    case GracePeriod = 'GRACE_PERIOD';
    case Expired = 'EXPIRED';
    case Cancelled = 'CANCELLED';
    case Suspended = 'SUSPENDED';
}
