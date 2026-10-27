<?php

namespace App\Repositories;

use App\Models\BudgetLine;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\PaymentStage;
use App\Models\User;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

class PaymentRepository
{
    /**
     * @return array<int, Money> bosqich ID => faol to'lovlar yig'indisi
     */
    public function activePaidByStage(Contract $contract): array
    {
        return Payment::query()
            ->active()
            ->where('contract_id', $contract->getKey())
            ->groupBy('payment_stage_id')
            ->selectRaw('payment_stage_id, SUM(amount) as total')
            ->toBase()
            ->pluck('total', 'payment_stage_id')
            ->map(fn (mixed $total): Money => Money::of((string) $total))
            ->all();
    }

    public function activeSpentForBudgetLine(BudgetLine $budgetLine): Money
    {
        $spent = Payment::query()
            ->active()
            ->where('budget_line_id', $budgetLine->getKey())
            ->sum('amount');

        return Money::of((string) $spent);
    }

    public function existsForBudgetLine(BudgetLine $budgetLine): bool
    {
        return Payment::query()->where('budget_line_id', $budgetLine->getKey())->exists();
    }

    /**
     * @return Collection<int, Payment>
     */
    public function registerFor(Contract $contract): Collection
    {
        return Payment::query()
            ->where('contract_id', $contract->getKey())
            ->with(['stage', 'budgetLine', 'creator', 'voider'])
            ->orderByDesc('paid_on')
            ->orderByDesc('id')
            ->get();
    }

    public function create(
        Contract $contract,
        PaymentStage $stage,
        BudgetLine $budgetLine,
        User $creator,
        Money $amount,
        CarbonImmutable $paidOn,
        string $reference,
    ): Payment {
        $payment = new Payment([
            'payment_stage_id' => $stage->getKey(),
            'budget_line_id' => $budgetLine->getKey(),
            'amount' => $amount,
            'paid_on' => $paidOn,
            'reference' => $reference,
        ]);
        $payment->contract_id = $contract->getKey();
        $payment->created_by = $creator->id;
        $payment->save();

        return $payment;
    }

    public function lockForUpdate(Payment $payment): Payment
    {
        return Payment::query()->whereKey($payment->getKey())->lockForUpdate()->firstOrFail();
    }

    public function void(Payment $payment, User $voidedBy, string $reason): Payment
    {
        $payment->forceFill([
            'voided_at' => now(),
            'voided_by' => $voidedBy->id,
            'void_reason' => $reason,
        ])->save();

        return $payment;
    }
}
