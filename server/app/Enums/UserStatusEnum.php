<?php

namespace App\Enums;

enum UserStatusEnum: string
{
    case Active = 'ACTIVE';
    case Inactive = 'INACTIVE';
    case Suspended = 'SUSPENDED';
}
