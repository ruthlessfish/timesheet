<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\TimeEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InvoiceItem>
 */
class InvoiceItemFactory extends Factory
{
    protected $model = InvoiceItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->randomFloat(2, 1, 100);
        $rate = fake()->randomFloat(2, 50, 200);
        $amount = $quantity * $rate;

        return [
            'invoice_id' => Invoice::factory(),
            'time_entry_id' => null,
            'description' => fake()->sentence(),
            'quantity' => $quantity,
            'rate' => $rate,
            'amount' => $amount,
        ];
    }

    /**
     * Associate the invoice item with a time entry.
     */
    public function forTimeEntry(TimeEntry $timeEntry): static
    {
        $hours = $timeEntry->duration / 60;
        $rate = $timeEntry->hourly_rate 
            ?? $timeEntry->project->hourly_rate 
            ?? $timeEntry->project->client->hourly_rate 
            ?? 0;

        return $this->state(fn (array $attributes) => [
            'time_entry_id' => $timeEntry->id,
            'description' => $timeEntry->description ?? $timeEntry->project->name,
            'quantity' => round($hours, 2),
            'rate' => $rate,
            'amount' => $hours * $rate,
        ]);
    }
}
