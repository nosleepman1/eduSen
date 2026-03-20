<?php

namespace App\Enums;

enum ClassroomStatusEnum: string
{
    case Draft = 'DRAFT';
    case Active = 'ACTIVE';
    case Closed = 'CLOSED';
    case Archived = 'ARCHIVED';
}
