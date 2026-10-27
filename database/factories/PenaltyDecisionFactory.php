<?php

namespace Database\Factories;

use App\Enums\PenaltyDecisionStatus;
use App\Models\Contract;
use App\Models\PenaltyDecision;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PenaltyDecision>
 */
class PenaltyDecisionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contract_id' => Contract::factory(),
            'status' => PenaltyDecisionStatus::Approved,
            'calculated_as_of' => '2026-03-15',
            'calculated_amount' => '37.50',
            'approved_amount' => null,
            'decided_by' => User::factory(),
        ];
    }
}
