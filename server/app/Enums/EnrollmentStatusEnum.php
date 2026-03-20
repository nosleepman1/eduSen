<?php

namespace App\Enums;

enum EnrollmentStatusEnum: string
{
    case Active = 'ACTIVE';
    case Transferred = 'TRANSFERRED';
    case Withdrawn = 'WITHDRAWN';
    case Archived = 'ARCHIVED';
}
