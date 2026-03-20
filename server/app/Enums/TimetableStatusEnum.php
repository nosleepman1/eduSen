<?php

namespace App\Enums;

enum TimetableStatusEnum: string
{
    case Draft = 'DRAFT';
    case Active = 'ACTIVE';
    case Archived = 'ARCHIVED';
}
