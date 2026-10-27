<?php

namespace Database\Factories;

use App\Models\Contract;
use App\Models\ContractItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContractItem>
 */
class ContractItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contract_id' => Contract::factory(),
            'name' => fake()->words(2, true),
            'paid_quantity' => 1000,
            'bonus_quantity' => 50,
            'unit_price' => '2.50',
        ];
    }
}
