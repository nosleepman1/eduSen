<?php

namespace App\Enums;

enum PersonnelStatusEnum: string
{
    case Active = 'ACTIVE';
    case Suspended = 'SUSPENDED';
    case Terminated = 'TERMINATED';
}
