<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\BudgetLine;
use App\Models\Contract;
use App\Models\User;
use App\Services\Contract\ContractService;
use App\Services\Delivery\DeliveryService;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(ContractService $contracts, DeliveryService $deliveries): void
    {
        $year = CarbonImmutable::today()->year;

        $executor = User::factory()->create([
            'name' => 'Ijrochi Aliyev',
            'email' => 'ijrochi@demo.uz',
            'role' => UserRole::Executor,
        ]);
        User::factory()->create(['name' => 'Boshliq Karimov', 'email' => 'boshliq@demo.uz', 'role' => UserRole::Head]);
        User::factory()->create(['name' => 'Buxgalter Rahimova', 'email' => 'buxgalter@demo.uz', 'role' => UserRole::Accountant]);

        BudgetLine::query()->create(['fiscal_year' => $year, 'expense_item' => 'Dori vositalari', 'month' => 3, 'allocated_amount' => Money::of('3000')]);
        BudgetLine::query()->create(['fiscal_year' => $year, 'expense_item' => 'Dori vositalari', 'month' => 4, 'allocated_amount' => Money::of('2000')]);

        $first = $this->contract($contracts, $executor, "SH-{$year}/001", "\"Farm Ta'minot\" MChJ", $year);
        $contracts->activate($first);

        $second = $this->contract($contracts, $executor, "SH-{$year}/002", '"Medline Distribution" MChJ', $year);
        $contracts->activate($second);
        $itemId = $second->items()->value('id');
        $deliveries->record($second, $executor, CarbonImmutable::create($year, 3, 14), [$itemId => ['paid' => 600, 'bonus' => 0]]);

        $this->contract($contracts, $executor, "SH-{$year}/003", '"Shifo Med" MChJ', $year);
    }

    private function contract(ContractService $contracts, User $executor, string $number, string $supplier, int $year): Contract
    {
        $contract = $contracts->create($executor, [
            'number' => $number,
            'supplier' => $supplier,
            'start_date' => "{$year}-03-02",
            'duration_days' => 10,
        ]);

        $contracts->addItem($contract, [
            'name' => 'Paratsetamol 500 mg',
            'paid_quantity' => 1000,
            'bonus_quantity' => 50,
            'unit_price' => Money::of('2.50'),
        ]);

        return $contract;
    }
}
