<?php

namespace Tests\Support;

use App\Models\BudgetLine;
use App\Models\Contract;
use App\Models\User;
use App\Services\Contract\ContractService;
use App\Services\Delivery\DeliveryService;
use App\Support\Money;
use Carbon\CarbonImmutable;

trait BuildsContracts
{
    protected function draftContract(?User $executor = null, string $year = '2026'): Contract
    {
        $executor ??= User::factory()->create();
        $service = app(ContractService::class);

        $contract = $service->create($executor, [
            'number' => 'SH-'.fake()->unique()->numerify('#####'),
            'supplier' => 'Yetkazuvchi MChJ',
            'start_date' => "{$year}-03-02",
            'duration_days' => 10,
        ]);

        $service->addItem($contract, [
            'name' => 'Paratsetamol',
            'paid_quantity' => 1000,
            'bonus_quantity' => 50,
            'unit_price' => Money::of('2.50'),
        ]);

        return $contract->refresh();
    }

    protected function activeContract(?User $executor = null, string $year = '2026'): Contract
    {
        $contract = $this->draftContract($executor, $year);
        app(ContractService::class)->activate($contract);

        return $contract->refresh();
    }

    protected function deliver(Contract $contract, string $date, int $paid, int $bonus = 0): void
    {
        app(DeliveryService::class)->record(
            $contract,
            $contract->creator,
            CarbonImmutable::parse($date),
            [$contract->items()->value('id') => ['paid' => $paid, 'bonus' => $bonus]],
        );
    }

    protected function budgetLine(int $month = 3, string $allocated = '3000.00', int $year = 2026): BudgetLine
    {
        return BudgetLine::factory()->create([
            'fiscal_year' => $year,
            'month' => $month,
            'allocated_amount' => $allocated,
        ]);
    }
}
