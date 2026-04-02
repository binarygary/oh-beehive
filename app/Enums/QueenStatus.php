<?php

declare(strict_types=1);

namespace App\Enums;

enum QueenStatus: string
{
    case Laying = 'laying';
    case NotLaying = 'not_laying';
    case SwarmCells = 'swarm_cells';
    case SupersedureCells = 'supersedure_cells';
}
