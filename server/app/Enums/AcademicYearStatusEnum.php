<?php

namespace App\Enums;

enum AcademicYearStatusEnum: string
{
    case Draft = 'DRAFT';
    case Active = 'ACTIVE';
    case Closed = 'CLOSED';
    case Archived = 'ARCHIVED';
}
