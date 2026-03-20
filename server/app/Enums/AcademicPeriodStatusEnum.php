<?php

namespace App\Enums;

enum AcademicPeriodStatusEnum: string
{
    case Draft = 'DRAFT';
    case Active = 'ACTIVE';
    case Closed = 'CLOSED';
    case Archived = 'ARCHIVED';
}
