<?php

namespace Tests\Unit;

use App\Enums\ContractStatus;
use App\Enums\StageCondition;
use App\Enums\StageStatus;
use App\Models\PaymentStage;
use App\Services\Accounting\StageLedger;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class StageLedgerTest extends TestCase
{
    /**
     * @return list<PaymentStage>
     */
    private function stages(?string $advanceDueDate = null): array
    {
        $advance = new PaymentStage(['sequence' => 1, 'name' => 'Avans', 'percent' => '30', 'condition' => StageCondition::OnActivation, 'due_date' => $advanceDueDate]);
        $advance->forceFill(['id' => 1, 'amount' => Money::of('750')]);

        $delivery = new PaymentStage(['sequence' => 2, 'name' => 'Yetkazib berish', 'percent' => '70', 'condition' => StageCondition::OnFullDelivery]);
        $delivery->forceFill(['id' => 2, 'amount' => Money::of('1750')]);

        return [$advance, $delivery];
    }

    /**
     * @param  array<int, Money>  $paid
     * @return list<StageStatus>
     */
    private function statuses(ContractStatus $status, array $paid, ?string $advanceDueDate = null, string $today = '2026-03-20'): array
    {
        $summaries = (new StageLedger)->summarize($status, $this->stages($advanceDueDate), $paid, Money::zero(), CarbonImmutable::parse($today));

        return array_map(fn ($summary) => $summary->status(), $summaries);
    }

    public function test_statuses_follow_conditions_and_register(): void
    {
        $this->assertSame([StageStatus::Planned, StageStatus::Planned], $this->statuses(ContractStatus::Draft, []));
        $this->assertSame([StageStatus::ReadyToPay, StageStatus::Planned], $this->statuses(ContractStatus::Active, []));
        $this->assertSame([StageStatus::PartiallyPaid, StageStatus::Planned], $this->statuses(ContractStatus::Active, [1 => Money::of('100')]));
        $this->assertSame([StageStatus::Paid, StageStatus::ReadyToPay], $this->statuses(ContractStatus::Completed, [1 => Money::of('750')]));
    }

    public function test_stage_is_overdue_when_due_date_passed_with_balance(): void
    {
        $this->assertSame(StageStatus::Overdue, $this->statuses(ContractStatus::Active, [1 => Money::of('100')], '2026-03-10')[0]);
        $this->assertSame(StageStatus::Paid, $this->statuses(ContractStatus::Active, [1 => Money::of('750')], '2026-03-10')[0]);
    }

    public function test_approved_penalty_is_withheld_from_final_stage(): void
    {
        $summaries = (new StageLedger)->summarize(
            ContractStatus::Completed,
            $this->stages(),
            [1 => Money::of('750')],
            Money::of('37.50'),
            CarbonImmutable::parse('2026-03-20'),
        );

        $this->assertSame('0.00', $summaries[0]->penaltyDeduction->amount);
        $this->assertSame('1712.50', $summaries[1]->payable()->amount);
        $this->assertSame('1712.50', $summaries[1]->remaining()->amount);
    }
}
