<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Inspection;

interface InspectionParserInterface
{
    public function parseRaw(string $rawNotes): array;

    public function parse(Inspection $inspection): void;
}
