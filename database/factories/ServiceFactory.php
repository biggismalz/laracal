<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Service>
 */
class ServiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(3, true),
            'slug' => $this->faker->unique()->slug(),
            'description' => $this->faker->optional()->paragraph(),
            'duration_minutes' => $this->faker->randomElement([30, 45, 60, 90, 120]),
            'price_cents' => $this->faker->numberBetween(5000, 25000),
            'deposit_cents' => $this->faker->boolean(50) ? $this->faker->numberBetween(2500, 10000) : null,
            'currency' => 'GBP',
            'buffer_before_minutes' => $this->faker->randomElement([0, 10, 15]),
            'buffer_after_minutes' => $this->faker->randomElement([0, 10, 15]),
            'is_active' => true,
        ];
    }
}
