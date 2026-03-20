<?php

namespace App\Enums;

enum TeachingAssignmentRoleEnum: string
{
    case Principal = 'PRINCIPAL';
    case Substitute = 'SUBSTITUTE';
    case CoTeacher = 'CO_TEACHER';
}
