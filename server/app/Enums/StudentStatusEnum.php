<?php

namespace App\Enums;

enum StudentStatusEnum: string
{
    case Active = 'ACTIVE';
    case Transferred = 'TRANSFERRED';
    case Graduated = 'GRADUATED';
    case Expelled = 'EXPELLED';
}
