<?php

declare(strict_types=1);

namespace App\Enums;

enum HiveStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case DeadOut = 'dead_out';

    public function label(): string
    {
        return match ($this) {
            HiveStatus::Active => 'Active',
            HiveStatus::Inactive => 'Inactive',
            HiveStatus::DeadOut => 'Dead Out',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            HiveStatus::Active => 'badge-success',
            HiveStatus::Inactive => 'badge-neutral',
            HiveStatus::DeadOut => 'badge-error',
        };
    }
}
