<?php

namespace App\Enums;

enum PhoneTypeEnum: string
{
    case Mobile = 'MOBILE';
    case Landline = 'LANDLINE';
    case Whatsapp = 'WHATSAPP';
    case Emergency = 'EMERGENCY';
}
