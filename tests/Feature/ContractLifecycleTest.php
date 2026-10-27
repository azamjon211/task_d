<?php

namespace Tests\Feature;

use App\Enums\ContractStatus;
use App\Enums\StageStatus;
use App\Models\Contract;
use App\Models\User;
use App\Services\Accounting\AccountingService;
use App\Services\Contract\ContractService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsContracts;
use Tests\TestCase;

class ContractLifecycleTest extends TestCase
{
    use BuildsContracts;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-03-20 10:00:00');
    }

    public function test_executor_creates_contract_with_default_payment_schedule_and_activates_it(): void
    {
        $executor = User::factory()->create();

        $this->actingAs($executor)->post(route('contracts.store'), [
            'number' => 'SH-1', 'supplier' => 'Farm MChJ', 'start_date' => '2026-03-02', 'duration_days' => 10,
        ])->assertSessionHasNoErrors();
        $contract = Contract::query()->where('number', 'SH-1')->firstOrFail();

        $this->actingAs($executor)->post(route('contracts.activate', $contract))
            ->assertSessionHasErrors(['general' => 'Faollashtirish uchun kamida 1 ta tovar qatori kerak.']);

        $this->actingAs($executor)->post(route('contracts.items.store', $contract), [
            'name' => 'Paratsetamol', 'paid_quantity' => 1000, 'bonus_quantity' => 50, 'unit_price' => '2,50',
        ])->assertSessionHasNoErrors();
        $this->actingAs($executor)->post(route('contracts.activate', $contract))->assertSessionHasNoErrors();

        $contract->refresh();
        $this->assertSame(ContractStatus::Active, $contract->status);
        $this->assertSame('2026-03-12', $contract->dueDate()->toDateString());
        $this->assertSame(['750.00', '1750.00'], $contract->stages->map(fn ($stage) => $stage->amount->amount)->all());
    }

    public function test_contract_total_cannot_exceed_database_money_limit(): void
    {
        $contract = $this->draftContract();

        $this->actingAs($contract->creator)->post(route('contracts.items.store', $contract), [
            'name' => 'Katta', 'paid_quantity' => 100000000, 'bonus_quantity' => 0, 'unit_price' => '9999999999999',
        ])->assertSessionHasErrors('unit_price');

        $this->assertSame(1, $contract->items()->count());
    }

    public function test_malformed_delivery_input_is_a_validation_error_not_a_crash(): void
    {
        $contract = $this->activeContract();

        $this->actingAs($contract->creator)->post(route('contracts.deliveries.store', $contract), [
            'delivered_on' => '2026-03-10',
            'items' => [$contract->items()->value('id') => 'abc'],
        ])->assertSessionHasErrors();

        $this->assertSame(0, $contract->deliveries()->count());
    }

    public function test_stage_due_date_is_set_in_draft_and_drives_overdue_status(): void
    {
        $contract = $this->draftContract();
        $advance = $contract->stages()->where('sequence', 1)->firstOrFail();

        $this->actingAs($contract->creator)->patch(route('contracts.stages.update', [$contract, $advance]), ['due_date' => '2026-03-10'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('contracts.show', $contract));
        app(ContractService::class)->activate($contract);

        $this->assertSame(StageStatus::Overdue, app(AccountingService::class)->stageSummaries($contract->refresh())[0]->status());

        $this->actingAs($contract->creator)->patch(route('contracts.stages.update', [$contract, $advance]), ['due_date' => '2026-03-25'])
            ->assertSessionHasErrors('general');
        $this->assertSame('2026-03-10', $advance->refresh()->due_date->toDateString());
    }

    public function test_items_cannot_change_after_activation(): void
    {
        $contract = $this->activeContract();

        $this->actingAs($contract->creator)->get(route('contracts.edit', $contract))
            ->assertRedirect(route('contracts.show', $contract));

        $this->actingAs($contract->creator)->post(route('contracts.items.store', $contract), [
            'name' => 'Yangi', 'paid_quantity' => 1, 'bonus_quantity' => 0, 'unit_price' => '1',
        ])->assertSessionHasErrors('general');
        $this->actingAs($contract->creator)->delete(route('contracts.items.destroy', [$contract, $contract->items->first()]))
            ->assertSessionHasErrors('general');

        $this->assertSame(1, $contract->items()->count());
    }

    public function test_over_delivery_is_rejected_with_exact_message_for_paid_and_bonus_separately(): void
    {
        $contract = $this->activeContract();
        $itemId = $contract->items()->value('id');
        $this->deliver($contract, '2026-03-10', 600, 40);

        $this->actingAs($contract->creator)->post(route('contracts.deliveries.store', $contract), [
            'delivered_on' => '2026-03-11',
            'items' => [$itemId => ['paid' => 401, 'bonus' => 0]],
        ])->assertSessionHasErrors(["items.{$itemId}.paid" => '«Paratsetamol»: pullik qoldiq 400 dona, siz 401 dona kiritdingiz.']);

        $this->actingAs($contract->creator)->post(route('contracts.deliveries.store', $contract), [
            'delivered_on' => '2026-03-11',
            'items' => [$itemId => ['paid' => 0, 'bonus' => 11]],
        ])->assertSessionHasErrors(["items.{$itemId}.bonus" => '«Paratsetamol»: bonus qoldiq 10 dona, siz 11 dona kiritdingiz.']);

        $this->assertSame(1, $contract->deliveries()->count());
    }

    public function test_contract_completes_automatically_when_paid_goods_fully_delivered(): void
    {
        $contract = $this->activeContract();
        $this->deliver($contract, '2026-03-10', 600);
        $this->assertSame(ContractStatus::Active, $contract->refresh()->status);

        $this->actingAs($contract->creator)->post(route('contracts.deliveries.store', $contract), [
            'delivered_on' => '2026-03-14',
            'items' => [$contract->items()->value('id') => ['paid' => 400, 'bonus' => 0]],
        ])->assertSessionHasNoErrors();

        $this->assertSame(ContractStatus::Completed, $contract->refresh()->status);
    }

    public function test_head_cannot_approve_more_than_calculated_penalty(): void
    {
        $contract = $this->activeContract();
        $head = User::factory()->head()->create();

        $this->actingAs($head)->post(route('contracts.penalty.approve', $contract), ['as_of' => '2026-03-15', 'approved_amount' => '40'])
            ->assertSessionHasErrors(['approved_amount' => "Tasdiqlanadigan summa (40,00) hisoblangan jarimadan (37,50) ko'p bo'lishi mumkin emas."]);
        $this->actingAs($head)->post(route('contracts.penalty.approve', $contract), ['as_of' => '2026-03-25'])
            ->assertSessionHasErrors('as_of');
    }
}
