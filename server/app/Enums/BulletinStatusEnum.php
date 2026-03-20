<?php

namespace App\Enums;

enum BulletinStatusEnum: string
{
    case Draft = 'DRAFT';
    case Validated = 'VALIDATED';
    case Published = 'PUBLISHED';
    case Locked = 'LOCKED';
    case Archived = 'ARCHIVED';
}
