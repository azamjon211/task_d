<?php

namespace App\Services\Accounting;

use App\Models\BudgetLine;
use App\Models\Payment;
use App\Support\Money;
use Illuminate\Database\Eloquent\Collection;

final readonly class AccountingOverview
{
    /**
     * @param  list<StageSummary>  $summaries
     * @param  Collection<int, Payment>  $activePayments
     * @param  Collection<int, Payment>  $voidedPayments
     * @param  Collection<int, BudgetLine>  $budgetLines
     */
    public function __construct(
        public Money $paidTotal,
        public array $summaries,
        public Money $approvedPenalty,
        public Money $penaltyToRecover,
        public Collection $activePayments,
        public Collection $voidedPayments,
        public Collection $budgetLines,
    ) {}
}
