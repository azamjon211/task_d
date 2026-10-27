<?php

namespace App\Services\Accounting;

use App\Enums\StageStatus;
use App\Models\PaymentStage;
use App\Support\Money;
use Carbon\CarbonImmutable;

final readonly class StageSummary
{
    /**
     * @param  Money  $amount  Bosqich summasi (foiz × pullik summa)
     * @param  Money  $penaltyDeduction  Ushlab qolinadigan tasdiqlangan jarima (faqat oxirgi bosqichda)
     * @param  Money  $paid  Faol to'lovlar yig'indisi
     * @param  CarbonImmutable  $asOf  Holat qaysi sanaga hisoblanadi ("Muddati o'tgan" uchun)
     */
    public function __construct(
        public PaymentStage $stage,
        public Money $amount,
        public Money $penaltyDeduction,
        public Money $paid,
        public bool $conditionMet,
        public CarbonImmutable $asOf,
    ) {}

    public function payable(): Money
    {
        return $this->amount->minus($this->penaltyDeduction);
    }

    public function remaining(): Money
    {
        return $this->payable()->minus($this->paid)->nonNegative();
    }

    public function overpaid(): Money
    {
        return $this->paid->minus($this->payable())->nonNegative();
    }

    public function isFullyPaid(): bool
    {
        return $this->remaining()->isZero();
    }

    public function status(): StageStatus
    {
        if (! $this->conditionMet) {
            return StageStatus::Planned;
        }

        if ($this->isFullyPaid()) {
            return StageStatus::Paid;
        }

        $dueDate = $this->stage->due_date;

        if ($dueDate !== null && $this->asOf->isAfter($dueDate)) {
            return StageStatus::Overdue;
        }

        return $this->paid->isPositive() ? StageStatus::PartiallyPaid : StageStatus::ReadyToPay;
    }
}
