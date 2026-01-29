<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'user_id' => function (array $attributes) {
                return Client::find($attributes['client_id'])->user_id;
            },
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'hourly_rate' => fake()->randomFloat(2, 75, 250),
            'budget' => fake()->randomFloat(2, 1000, 10000),
            'status' => 'active',
            'start_date' => now()->subDays(30),
            'end_date' => null,
        ];
    }

    /**
     * Indicate that the project is on hold.
     */
    public function onHold(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'on_hold',
        ]);
    }

    /**
     * Indicate that the project is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'end_date' => now()->subDays(7),
        ]);
    }

    /**
     * Indicate that the project has no hourly rate set.
     */
    public function withoutRate(): static
    {
        return $this->state(fn (array $attributes) => [
            'hourly_rate' => null,
        ]);
    }
}
