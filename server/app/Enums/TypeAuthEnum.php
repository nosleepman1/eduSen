<?php

namespace App\Enums;

enum TypeAuthEnum: string
{
    case EmailPassword = 'EMAIL_PASSWORD';
    case OtpPhone = 'OTP_PHONE';
}
