<?php

namespace App\Services\Accounting;

use App\Exceptions\BusinessRuleException;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\User;
use App\Repositories\BudgetLineRepository;
use App\Repositories\ContractRepository;
use App\Repositories\PaymentRepository;
use Illuminate\Support\Facades\DB;

/**
 * Qulflar tartibi hamma joyda bir xil: shartnoma → smeta qatori → to'lov (aks holda deadlock bo'ladi).
 */
class PaymentService
{
    public function __construct(
        private readonly PaymentRules $rules,
        private readonly AccountingService $accounting,
        private readonly ContractRepository $contracts,
        private readonly BudgetLineRepository $budgetLines,
        private readonly PaymentRepository $payments,
    ) {}

    public function register(Contract $contract, User $accountant, PaymentData $data): Payment
    {
        return DB::transaction(function () use ($contract, $accountant, $data): Payment {
            $contract = $this->contracts->lockForUpdate($contract);
            $budgetLine = $this->budgetLines->lockForUpdate($data->budgetLineId);

            $ledger = $this->accounting->stageSummaries($contract);
            $budgetRemaining = $budgetLine->allocated_amount->minus($this->payments->activeSpentForBudgetLine($budgetLine));

            $this->rules->assertPayable(
                $contract,
                $ledger,
                $data->stageId,
                $data->amount,
                $data->paidOn,
                $budgetLine,
                $budgetRemaining,
            );

            $stage = collect($ledger)->first(fn (StageSummary $summary): bool => $summary->stage->getKey() === $data->stageId)->stage;

            return $this->payments->create(
                $contract,
                $stage,
                $budgetLine,
                $accountant,
                $data->amount,
                $data->paidOn,
                $data->reference,
            );
        });
    }

    public function void(Contract $contract, Payment $payment, User $accountant, string $reason): Payment
    {
        return DB::transaction(function () use ($contract, $payment, $accountant, $reason): Payment {
            $this->contracts->lockForUpdate($contract);
            $this->budgetLines->lockForUpdate($payment->budget_line_id);
            $payment = $this->payments->lockForUpdate($payment);

            if ($payment->isVoided()) {
                throw new BusinessRuleException(
                    "Bu to'lov allaqachon {$payment->voided_at->format('d.m.Y H:i')} da bekor qilingan.",
                );
            }

            return $this->payments->void($payment, $accountant, $reason);
        });
    }
}
