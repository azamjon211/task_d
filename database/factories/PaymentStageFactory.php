<?php

namespace Database\Factories;

use App\Enums\StageCondition;
use App\Models\Contract;
use App\Models\PaymentStage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentStage>
 */
class PaymentStageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contract_id' => Contract::factory(),
            'sequence' => 1,
            'name' => 'Avans',
            'percent' => '30.00',
            'condition' => StageCondition::OnActivation,
            'due_date' => null,
        ];
    }
}
