<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\TimeEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TimeEntry>
 */
class TimeEntryFactory extends Factory
{
    protected $model = TimeEntry::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startTime = fake()->dateTimeBetween('-30 days', 'now');
        $endTime = (clone $startTime)->modify('+'.fake()->numberBetween(30, 480).' minutes');
        $durationMinutes = (int) (($endTime->getTimestamp() - $startTime->getTimestamp()) / 60);

        return [
            'project_id' => Project::factory(),
            'user_id' => function (array $attributes) {
                return Project::find($attributes['project_id'])->user_id;
            },
            'description' => fake()->optional()->sentence(),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'duration' => $durationMinutes,
            'hourly_rate' => null, // Will cascade from project/client
            'is_billable' => true,
            'is_invoiced' => false,
        ];
    }

    /**
     * Indicate that the time entry is currently running (active timer).
     */
    public function running(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => now()->subHours(1),
            'end_time' => null,
            'duration' => null,
        ]);
    }

    /**
     * Indicate that the time entry is non-billable.
     */
    public function nonBillable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_billable' => false,
        ]);
    }

    /**
     * Indicate that the time entry has been invoiced.
     */
    public function invoiced(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_invoiced' => true,
        ]);
    }

    /**
     * Set a custom hourly rate for this entry.
     */
    public function withRate(float $rate): static
    {
        return $this->state(fn (array $attributes) => [
            'hourly_rate' => $rate,
        ]);
    }

    /**
     * Set a specific duration in minutes.
     */
    public function withDuration(int $minutes): static
    {
        return $this->state(function (array $attributes) use ($minutes) {
            $startTime = $attributes['start_time'] ?? now()->subMinutes($minutes);
            $endTime = (clone $startTime)->modify("+{$minutes} minutes");

            return [
                'start_time' => $startTime,
                'end_time' => $endTime,
                'duration' => $minutes,
            ];
        });
    }
}
