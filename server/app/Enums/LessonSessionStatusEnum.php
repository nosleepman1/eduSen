<?php

namespace App\Enums;

enum LessonSessionStatusEnum: string
{
    case Scheduled = 'SCHEDULED';
    case PendingStart = 'PENDING_START';
    case InProgress = 'IN_PROGRESS';
    case PendingValidation = 'PENDING_VALIDATION';
    case Completed = 'COMPLETED';
    case Cancelled = 'CANCELLED';
    case Postponed = 'POSTPONED';
    case Archived = 'ARCHIVED';
}
