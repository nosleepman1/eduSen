<?php

namespace App\Enums;

enum GradeStatusEnum: string
{
    case Pending = 'PENDING';
    case Graded = 'GRADED';
    case Absent = 'ABSENT';
    case Dispensed = 'DISPENSED';
}
