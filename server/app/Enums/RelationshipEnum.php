<?php

namespace App\Enums;

enum RelationshipEnum: string
{
    case Father = 'FATHER';
    case Mother = 'MOTHER';
    case Guardian = 'GUARDIAN';
    case Tutor = 'TUTOR';
    case Other = 'OTHER';
}
