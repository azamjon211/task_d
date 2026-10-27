<?php

namespace Database\Factories;

use App\Models\BudgetLine;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\PaymentStage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contract_id' => Contract::factory(),
            'payment_stage_id' => PaymentStage::factory(),
            'budget_line_id' => BudgetLine::factory(),
            'amount' => '100.00',
            'paid_on' => '2026-03-05',
            'reference' => fake()->numerify('PP-####'),
            'created_by' => User::factory(),
        ];
    }
}
