<?php

namespace App\Enums;

enum RoleEnum: string
{
    case SuperAdmin = 'SUPER_ADMIN';
    case Owner = 'OWNER';
    case Admin = 'ADMIN';
    case Teacher = 'TEACHER';
    case Supervisor = 'SUPERVISOR';
    case Accountant = 'ACCOUNTANT';
    case Secretary = 'SECRETARY';
    case Librarian = 'LIBRARIAN';
}
