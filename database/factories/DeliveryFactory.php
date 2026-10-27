<?php

namespace Database\Factories;

use App\Models\Contract;
use App\Models\Delivery;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Delivery>
 */
class DeliveryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contract_id' => Contract::factory(),
            'delivered_on' => '2026-03-10',
            'created_by' => User::factory(),
        ];
    }
}
