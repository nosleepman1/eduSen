<?php

namespace App\Enums;

enum RegistrationRequestStatusEnum: string
{
    case Pending = 'PENDING';
    case Approved = 'APPROVED';
    case Rejected = 'REJECTED';
}
