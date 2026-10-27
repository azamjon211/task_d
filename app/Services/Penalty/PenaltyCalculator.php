<?php

namespace App\Services\Penalty;

use App\Support\Money;
use Carbon\CarbonImmutable;

class PenaltyCalculator
{
    public const string DAILY_RATE = '0.005';

    public const string CAP_RATE = '0.5';

    /**
     * @param  array<string, Money>  $deliveredByDate  Partiya sanasi (Y-m-d) => o'sha kuni kelgan pullik summa
     */
    public function calculate(
        CarbonImmutable $dueDate,
        Money $paidTotal,
        array $deliveredByDate,
        CarbonImmutable $asOf,
    ): PenaltyResult {
        $dueDate = $dueDate->startOfDay();
        $asOf = $asOf->startOfDay();
        ksort($deliveredByDate);

        $cap = $this->undeliveredAfter($dueDate, $paidTotal, $deliveredByDate)->multipliedBy(self::CAP_RATE);

        $days = [];
        $accrued = Money::zero();

        for ($day = $dueDate->addDay(); $day->lessThanOrEqualTo($asOf); $day = $day->addDay()) {
            $undelivered = $this->undeliveredAfter($day->subDay(), $paidTotal, $deliveredByDate);

            if (! $undelivered->isPositive()) {
                break;
            }

            $penalty = $undelivered->multipliedBy(self::DAILY_RATE);
            $accrued = $accrued->plus($penalty);
            $days[] = new PenaltyDay($day, $undelivered, $penalty);
        }

        return new PenaltyResult(
            dueDate: $dueDate,
            asOf: $asOf,
            days: $days,
            accrued: $accrued,
            cap: $cap,
            total: $accrued->min($cap),
        );
    }

    /**
     * @param  array<string, Money>  $deliveredByDate  sana bo'yicha tartiblangan
     */
    private function undeliveredAfter(CarbonImmutable $date, Money $paidTotal, array $deliveredByDate): Money
    {
        $limit = $date->toDateString();
        $undelivered = $paidTotal;

        foreach ($deliveredByDate as $deliveredOn => $amount) {
            if ($deliveredOn > $limit) {
                break;
            }

            $undelivered = $undelivered->minus($amount);
        }

        return $undelivered->nonNegative();
    }
}
