<?php

declare(strict_types=1);

namespace App\Enums;

enum VarroaMethod: string
{
    case SugarRoll = 'sugar_roll';
    case AlcoholWash = 'alcohol_wash';
    case StickyBoard = 'sticky_board';

    public function label(): string
    {
        return match ($this) {
            VarroaMethod::SugarRoll => 'Sugar Roll',
            VarroaMethod::AlcoholWash => 'Alcohol Wash',
            VarroaMethod::StickyBoard => 'Sticky Board',
        };
    }
}
