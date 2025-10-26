<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AvailabilityRule>
 */
class AvailabilityRuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'service_id' => null,
            'day_of_week' => $this->faker->numberBetween(0, 6),
            'start_time' => $this->faker->randomElement(['09:00:00', '10:00:00', '13:00:00']),
            'end_time' => $this->faker->randomElement(['12:00:00', '15:00:00', '17:00:00']),
            'timezone' => config('app.timezone'),
            'capacity' => 1,
            'is_active' => true,
        ];
    }
}
