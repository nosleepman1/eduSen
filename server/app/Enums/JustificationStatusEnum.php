<?php

namespace App\Enums;

enum JustificationStatusEnum: string
{
    case Pending = 'PENDING';
    case Approved = 'APPROVED';
    case Rejected = 'REJECTED';
}
