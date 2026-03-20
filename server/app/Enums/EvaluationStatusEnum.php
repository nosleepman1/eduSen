<?php

namespace App\Enums;

enum EvaluationStatusEnum: string
{
    case Draft = 'DRAFT';
    case Open = 'OPEN';
    case Closed = 'CLOSED';
    case Locked = 'LOCKED';
    case Archived = 'ARCHIVED';
}
