<?php

namespace App\Enums;

enum FeatureTypeEnum: string
{
    case BOOLEAN = 'BOOLEAN';
    case QUOTA = 'QUOTA';
    case METERED = 'METERED';
}
