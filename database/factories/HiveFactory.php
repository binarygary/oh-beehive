<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\HiveStatus;
use App\Models\Hive;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Hive>
 */
class HiveFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => 'Hive '.$this->faker->unique()->numberBetween(1, 99),
            'location' => $this->faker->randomElement(['Back yard', 'Front garden', 'Orchard', null]),
            'acquired_at' => $this->faker->optional()->date(),
            'status' => HiveStatus::Active,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(['status' => HiveStatus::Inactive]);
    }

    public function deadOut(): static
    {
        return $this->state(['status' => HiveStatus::DeadOut]);
    }
}
