<?php

namespace App\Services\Accounting;

use App\Models\Contract;
use App\Models\Payment;
use App\Repositories\BudgetLineRepository;
use App\Repositories\ContractRepository;
use App\Repositories\PaymentRepository;
use App\Services\Penalty\PenaltyService;
use App\Support\Money;
use Carbon\CarbonImmutable;

class AccountingService
{
    public function __construct(
        private readonly StageLedger $ledger,
        private readonly ContractRepository $contracts,
        private readonly PaymentRepository $payments,
        private readonly BudgetLineRepository $budgetLines,
        private readonly PenaltyService $penalties,
    ) {}

    public function overview(Contract $contract): AccountingOverview
    {
        $approvedPenalty = $this->penalties->approvedAmount($contract);
        $summaries = $this->summarize($contract, $approvedPenalty);
        [$voided, $active] = $this->payments->registerFor($contract)
            ->partition(fn (Payment $payment): bool => $payment->isVoided());

        return new AccountingOverview(
            paidTotal: $this->contracts->paidTotal($contract),
            summaries: $summaries,
            approvedPenalty: $approvedPenalty,
            penaltyToRecover: $this->penaltyToRecover($approvedPenalty, $summaries),
            activePayments: $active->values(),
            voidedPayments: $voided->values(),
            budgetLines: $this->budgetLines->allWithSpent(),
        );
    }

    /**
     * @return list<StageSummary>
     */
    public function stageSummaries(Contract $contract): array
    {
        return $this->summarize($contract, $this->penalties->approvedAmount($contract));
    }

    /**
     * @return list<StageSummary>
     */
    private function summarize(Contract $contract, Money $approvedPenalty): array
    {
        return $this->ledger->summarize(
            $contract->status,
            $this->contracts->stages($contract),
            $this->payments->activePaidByStage($contract),
            $approvedPenalty,
            CarbonImmutable::today(),
        );
    }

    /**
     * @param  list<StageSummary>  $summaries
     */
    private function penaltyToRecover(Money $approvedPenalty, array $summaries): Money
    {
        $last = end($summaries);

        if ($last === false) {
            return $approvedPenalty;
        }

        return $approvedPenalty->minus($last->penaltyDeduction)->plus($last->overpaid());
    }
}
