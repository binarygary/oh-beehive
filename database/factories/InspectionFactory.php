<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\QueenStatus;
use App\Models\Hive;
use App\Models\Inspection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Inspection>
 */
class InspectionFactory extends Factory
{
    public function definition(): array
    {
        $hive = Hive::factory()->create();

        return [
            'hive_id' => $hive->id,
            'user_id' => $hive->user_id,
            'inspected_at' => $this->faker->dateTimeThisYear(),
            'raw_notes' => $this->faker->paragraph(),
            'queen_seen' => $this->faker->boolean(),
            'queen_status' => $this->faker->randomElement(QueenStatus::cases()),
            'eggs_present' => $this->faker->boolean(),
            'larvae_present' => $this->faker->boolean(),
            'capped_brood_present' => $this->faker->boolean(),
            'brood_pattern_score' => $this->faker->numberBetween(1, 5),
            'frames_of_brood' => $this->faker->numberBetween(1, 10),
            'frames_of_bees' => $this->faker->numberBetween(2, 20),
            'frames_of_honey' => $this->faker->numberBetween(0, 10),
            'honey_stores_score' => $this->faker->numberBetween(1, 5),
            'varroa_count' => null,
            'varroa_method' => null,
            'temperament_score' => $this->faker->numberBetween(1, 5),
            'disease_observations' => [],
            'overall_health_score' => $this->faker->numberBetween(1, 5),
            'feeding_done' => false,
            'feeding_notes' => null,
            'treatment_applied' => null,
            'supers_added' => 0,
            'supers_removed' => 0,
            'weather' => $this->faker->randomElement(['sunny', 'cloudy', 'warm', 'cool', null]),
            'followup_questions' => null,
        ];
    }
}
