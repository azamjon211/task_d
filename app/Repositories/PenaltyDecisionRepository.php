<?php

namespace App\Repositories;

use App\Enums\PenaltyDecisionStatus;
use App\Models\Contract;
use App\Models\PenaltyDecision;
use App\Models\User;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

class PenaltyDecisionRepository
{
    public function latestFor(Contract $contract): ?PenaltyDecision
    {
        return PenaltyDecision::query()
            ->where('contract_id', $contract->getKey())
            ->orderByDesc('id')
            ->first();
    }

    /**
     * @return Collection<int, PenaltyDecision>
     */
    public function historyFor(Contract $contract): Collection
    {
        return PenaltyDecision::query()
            ->where('contract_id', $contract->getKey())
            ->with('decider')
            ->orderByDesc('id')
            ->get();
    }

    public function create(
        Contract $contract,
        User $decidedBy,
        PenaltyDecisionStatus $status,
        CarbonImmutable $calculatedAsOf,
        Money $calculatedAmount,
        ?Money $approvedAmount,
        ?string $comment,
    ): PenaltyDecision {
        $decision = new PenaltyDecision([
            'status' => $status,
            'calculated_as_of' => $calculatedAsOf,
            'calculated_amount' => $calculatedAmount,
            'approved_amount' => $approvedAmount,
            'comment' => $comment,
        ]);
        $decision->contract_id = $contract->getKey();
        $decision->decided_by = $decidedBy->id;
        $decision->save();

        return $decision;
    }
}
