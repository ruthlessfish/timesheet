<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Expense;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Expense>
 */
class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

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
            'description' => fake()->sentence(),
            'amount' => fake()->randomFloat(2, 10, 500),
            'expense_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'category' => fake()->randomElement(['Software', 'Hardware', 'Travel', 'Office Supplies', 'Hosting', null]),
            'is_billable' => true,
            'is_invoiced' => false,
        ];
    }

    /**
     * Indicate that the expense is not billable.
     */
    public function nonBillable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_billable' => false,
        ]);
    }

    /**
     * Indicate that the expense has been invoiced.
     */
    public function invoiced(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_invoiced' => true,
        ]);
    }
}
