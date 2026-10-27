<?php

namespace Database\Factories;

use App\Enums\ContractStatus;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contract>
 */
class ContractFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => 'SH-'.fake()->unique()->numerify('####/###'),
            'supplier' => fake()->company(),
            'start_date' => '2026-03-02',
            'duration_days' => 10,
            'status' => ContractStatus::Draft,
            'created_by' => User::factory(),
        ];
    }
}
