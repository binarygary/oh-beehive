<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Contracts\InspectionParserInterface;
use App\Enums\QueenStatus;
use App\Models\Inspection;

class FakeInspectionParserService implements InspectionParserInterface
{
    /**
     * @var array<string, mixed>
     */
    public array $parsedData = [
        'queen_seen' => true,
        'queen_status' => QueenStatus::Laying,
        'brood_pattern_score' => 5,
        'overall_health_score' => 5,
    ];

    /**
     * Parse raw notes text and return extracted field values.
     *
     * @param string $rawNotes
     * @return array<string, mixed>
     */
    public function parseRaw(string $rawNotes): array
    {
        return $this->parsedData;
    }

    /**
     * Parse raw notes from an inspection and persist the extracted fields.
     */
    public function parse(Inspection $inspection): void
    {
        $inspection->update($this->parsedData);
    }
}
