<?php

namespace App\Services\Penalty;

use App\Support\Money;
use Carbon\CarbonImmutable;

final readonly class PenaltyResult
{
    /**
     * @param  list<PenaltyDay>  $days  Jarima hisoblangan kunlar
     * @param  Money  $accrued  Kunlik jarimalar yig'indisi (limitgacha)
     * @param  Money  $cap  Limit: muddat kuni oxiridagi kelmagan pullik summaning 50%
     * @param  Money  $total  Yakuniy jarima = min(accrued, cap)
     */
    public function __construct(
        public CarbonImmutable $dueDate,
        public CarbonImmutable $asOf,
        public array $days,
        public Money $accrued,
        public Money $cap,
        public Money $total,
    ) {}

    public function lateDays(): int
    {
        return count($this->days);
    }

    public function isCapApplied(): bool
    {
        return $this->accrued->greaterThan($this->cap);
    }

    public function isBeforeDueDate(): bool
    {
        return $this->asOf->lessThanOrEqualTo($this->dueDate);
    }
}
