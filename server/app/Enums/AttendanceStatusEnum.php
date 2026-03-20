<?php

namespace App\Enums;

enum AttendanceStatusEnum: string
{
    case Present = 'PRESENT';
    case Absent = 'ABSENT';
    case Late = 'LATE';
    case Excused = 'EXCUSED';
    case Dispensed = 'DISPENSED';
}
