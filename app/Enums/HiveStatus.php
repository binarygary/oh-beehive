<?php

declare(strict_types=1);

namespace App\Enums;

enum HiveStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case DeadOut = 'dead_out';
}
