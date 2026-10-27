<?php

namespace App\Services\Accounting;

use App\Enums\StageCondition;
use App\Exceptions\BusinessRuleException;
use App\Models\BudgetLine;
use App\Models\Contract;
use App\Support\Money;
use Carbon\CarbonImmutable;

class PaymentRules
{
    /**
     * @param  list<StageSummary>  $ledger  shartnomaning barcha bosqichlari, tartib bo'yicha
     */
    public function assertPayable(
        Contract $contract,
        array $ledger,
        int $stageId,
        Money $amount,
        CarbonImmutable $paidOn,
        BudgetLine $budgetLine,
        Money $budgetRemaining,
    ): void {
        $this->assertContractInForce($contract);

        $target = $this->findStage($ledger, $stageId);

        $this->assertConditionMet($target);
        $this->assertPreviousStagesPaid($ledger, $target);
        $this->assertAmountFitsStage($target, $amount);
        $this->assertBudgetAllows($budgetLine, $budgetRemaining, $amount, $paidOn);
    }

    private function assertContractInForce(Contract $contract): void
    {
        if (! $contract->status->isInForce()) {
            throw new BusinessRuleException(
                "To'lov qabul qilinmadi: shartnoma faol emas (holati: {$contract->status->label()}). "
                ."To'lov faqat Faol yoki Yakunlangan shartnomaga kiritiladi.",
            );
        }
    }

    /**
     * @param  list<StageSummary>  $ledger
     */
    private function findStage(array $ledger, int $stageId): StageSummary
    {
        foreach ($ledger as $summary) {
            if ($summary->stage->getKey() === $stageId) {
                return $summary;
            }
        }

        throw new BusinessRuleException('Tanlangan bosqich bu shartnomaga tegishli emas.', 'payment_stage_id');
    }

    private function assertConditionMet(StageSummary $target): void
    {
        if ($target->conditionMet) {
            return;
        }

        $reason = match ($target->stage->condition) {
            StageCondition::OnFullDelivery => "pullik tovar hali to'liq kelmagan",
            StageCondition::OnActivation => 'shartnoma hali faollashtirilmagan',
        };

        throw new BusinessRuleException(
            "To'lov qabul qilinmadi: «{$target->stage->name}» bosqichining to'lash sharti bajarilmagan — {$reason}.",
            'payment_stage_id',
        );
    }

    /**
     * @param  list<StageSummary>  $ledger
     */
    private function assertPreviousStagesPaid(array $ledger, StageSummary $target): void
    {
        foreach ($ledger as $summary) {
            if ($summary->stage->sequence >= $target->stage->sequence) {
                break;
            }

            if (! $summary->isFullyPaid()) {
                throw new BusinessRuleException(
                    "To'lov qabul qilinmadi: oldingi «{$summary->stage->name}» bosqichi to'liq to'lanmagan "
                    ."(qoldiq: {$summary->remaining()->format()}). Avval uni to'lang.",
                    'payment_stage_id',
                );
            }
        }
    }

    private function assertAmountFitsStage(StageSummary $target, Money $amount): void
    {
        if (! $amount->isPositive()) {
            throw new BusinessRuleException("To'lov summasi noldan katta bo'lishi kerak.", 'amount');
        }

        $remaining = $target->remaining();

        if ($remaining->isZero()) {
            throw new BusinessRuleException(
                "To'lov qabul qilinmadi: «{$target->stage->name}» bosqichi to'liq to'langan, qoldiq 0,00.",
                'amount',
            );
        }

        if ($amount->greaterThan($remaining)) {
            $penaltyNote = $target->penaltyDeduction->isPositive()
                ? " (tasdiqlangan jarima {$target->penaltyDeduction->format()} ushlab qolingan)"
                : '';

            throw new BusinessRuleException(
                "To'lov qabul qilinmadi: summa ({$amount->format()}) «{$target->stage->name}» bosqichining "
                ."qolgan summasidan ({$remaining->format()}){$penaltyNote} ko'p.",
                'amount',
            );
        }
    }

    private function assertBudgetAllows(BudgetLine $budgetLine, Money $budgetRemaining, Money $amount, CarbonImmutable $paidOn): void
    {
        if ($paidOn->year !== $budgetLine->fiscal_year) {
            throw new BusinessRuleException(
                "To'lov qabul qilinmadi: to'lov sanasi ({$paidOn->format('d.m.Y')}) smeta moliyaviy yiliga "
                ."({$budgetLine->fiscal_year}) mos emas.",
                'paid_on',
            );
        }

        if ($amount->greaterThan($budgetRemaining)) {
            throw new BusinessRuleException(
                "To'lov qabul qilinmadi: smeta qoldig'i yetarli emas — {$budgetLine->label()} qoldig'i "
                ."{$budgetRemaining->format()}, to'lov summasi {$amount->format()}.",
                'budget_line_id',
            );
        }
    }
}
