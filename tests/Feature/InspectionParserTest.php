<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\InspectionParserInterface;
use App\Models\Hive;
use App\Models\Inspection;
use App\Services\InspectionParserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Unit\FakeInspectionParserService;

class InspectionParserTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_parse_inspection_notes_using_fake(): void
    {
        // Arrange
        $hive = Hive::factory()->create();
        $inspection = Inspection::factory()->create(['hive_id' => $hive->id, 'raw_notes' => 'Queen seen, laying well.']);
        $fakeParser = new FakeInspectionParserService();

        // Act
        $this->app->instance(InspectionParserInterface::class, $fakeParser);
        $parser = $this->app->make(InspectionParserInterface::class);
        $parser->parse($inspection);

        // Assert
        $inspection->refresh();
        $this->assertTrue($inspection->queen_seen);
        $this->assertEquals(\App\Enums\QueenStatus::Laying, $inspection->queen_status);
        $this->assertEquals(5, $inspection->brood_pattern_score);
    }
}
