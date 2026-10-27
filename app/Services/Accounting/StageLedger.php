<?php

namespace App\Services\Accounting;

use App\Enums\ContractStatus;
use App\Enums\StageCondition;
use App\Models\PaymentStage;
use App\Support\Money;
use Carbon\CarbonImmutable;

class StageLedger
{
    /**
     * @param  iterable<PaymentStage>  $stages  sequence bo'yicha tartiblangan
     * @param  array<int, Money>  $paidByStage  bosqich ID => faol to'lovlar yig'indisi
     * @param  Money  $approvedPenalty  Tasdiqlangan jarima — oxirgi (yakuniy) bosqichdan ushlab qolinadi
     * @return list<StageSummary>
     */
    public function summarize(
        ContractStatus $contractStatus,
        iterable $stages,
        array $paidByStage,
        Money $approvedPenalty,
        CarbonImmutable $today,
    ): array {
        $stages = collect($stages)->values();
        $lastIndex = $stages->count() - 1;

        return $stages->map(function (PaymentStage $stage, int $index) use ($contractStatus, $paidByStage, $approvedPenalty, $today, $lastIndex): StageSummary {
            $amount = $stage->amount ?? Money::zero();
            $deduction = $index === $lastIndex ? $approvedPenalty->min($amount) : Money::zero();
            $paid = $paidByStage[$stage->getKey()] ?? Money::zero();
            $conditionMet = $this->isConditionMet($stage->condition, $contractStatus);

            return new StageSummary($stage, $amount, $deduction, $paid, $conditionMet, $today);
        })->all();
    }

    public function isConditionMet(StageCondition $condition, ContractStatus $contractStatus): bool
    {
        return match ($condition) {
            StageCondition::OnActivation => $contractStatus->isInForce(),
            StageCondition::OnFullDelivery => $contractStatus === ContractStatus::Completed,
        };
    }
}
