<?php

namespace Tests\Unit;

use App\Services\Penalty\PenaltyCalculator;
use App\Services\Penalty\PenaltyResult;
use App\Support\Money;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class PenaltyCalculatorTest extends TestCase
{
    private const string DUE_DATE = '2026-03-12';

    /**
     * @param  array<string, string>  $deliveries  sana => kelgan pullik summa
     */
    private function calculate(string $asOf, array $deliveries = [], string $paidTotal = '2500'): PenaltyResult
    {
        return (new PenaltyCalculator)->calculate(
            CarbonImmutable::parse(self::DUE_DATE),
            Money::of($paidTotal),
            array_map(fn (string $amount): Money => Money::of($amount), $deliveries),
            CarbonImmutable::parse($asOf),
        );
    }

    public function test_example_1_nothing_delivered(): void
    {
        $result = $this->calculate('2026-03-15');

        $this->assertSame(3, $result->lateDays());
        $this->assertSame('37.50', $result->total->amount);
        $this->assertSame('1250.00', $result->cap->amount);
        $this->assertSame(['12.50', '12.50', '12.50'], array_map(fn ($day) => $day->penalty->amount, $result->days));
    }

    public function test_example_2_partial_delivery_counts_from_next_day(): void
    {
        $result = $this->calculate('2026-03-16', ['2026-03-14' => '1500']);

        $this->assertSame(['2500.00', '2500.00', '1000.00', '1000.00'], array_map(fn ($day) => $day->undelivered->amount, $result->days));
        $this->assertSame('35.00', $result->total->amount);
    }

    public function test_total_is_capped_at_half_of_undelivered_amount_at_end_of_due_date(): void
    {
        $capped = $this->calculate('2027-01-01', ['2026-03-12' => '2000']);

        $this->assertSame('250.00', $capped->cap->amount);
        $this->assertSame('250.00', $capped->total->amount);
        $this->assertTrue($capped->isCapApplied());

        $this->assertSame('500.00', $capped->days[0]->undelivered->amount);
    }

    public function test_no_penalty_when_calculation_date_is_on_or_before_due_date(): void
    {
        $this->assertSame('0.00', $this->calculate('2026-03-12')->total->amount);
        $this->assertSame(0, $this->calculate('2026-03-05')->lateDays());
        $this->assertTrue($this->calculate('2026-03-05')->isBeforeDueDate());
    }

    public function test_days_without_undelivered_amount_are_not_counted(): void
    {
        $result = $this->calculate('2026-03-20', ['2026-03-13' => '2500']);

        $this->assertSame(1, $result->lateDays());
        $this->assertSame('12.50', $result->total->amount);
    }

    public function test_daily_penalty_is_rounded_before_summing(): void
    {
        $result = $this->calculate('2026-03-15', paidTotal: '1.00');

        $this->assertSame('0.03', $result->total->amount);
    }
}
