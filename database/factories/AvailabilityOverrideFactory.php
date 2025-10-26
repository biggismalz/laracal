<?php

namespace Database\Factories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AvailabilityOverride>
 */
class AvailabilityOverrideFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(['open', 'closed']);
        $startTime = $this->faker->randomElement(['09:00:00', '10:00:00', null]);
        $endTime = $startTime ? Carbon::parse($startTime)->addHours(2)->format('H:i:s') : null;

        return [
            'service_id' => null,
            'date' => $this->faker->dateTimeBetween('now', '+2 weeks')->format('Y-m-d'),
            'start_time' => $type === 'open' ? $startTime : null,
            'end_time' => $type === 'open' ? $endTime : null,
            'type' => $type,
            'timezone' => null,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
