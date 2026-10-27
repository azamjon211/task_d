<?php

namespace Database\Factories;

use App\Models\BudgetLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BudgetLine>
 */
class BudgetLineFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'fiscal_year' => 2026,
            'expense_item' => 'Dori vositalari',
            'month' => fake()->unique()->numberBetween(1, 12),
            'allocated_amount' => '3000.00',
        ];
    }
}
