<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\PenaltyDecision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsContracts;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use BuildsContracts;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-03-20 10:00:00');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('contracts.index'))->assertRedirect(route('login'));
    }

    public function test_executor_cannot_open_or_change_another_executors_contract_by_id(): void
    {
        $foreign = $this->activeContract();
        $intruder = User::factory()->create();

        $this->actingAs($intruder)->get(route('contracts.show', $foreign))->assertNotFound();
        $this->actingAs($intruder)->post(route('contracts.deliveries.store', $foreign), [
            'delivered_on' => '2026-03-10',
            'items' => [$foreign->items()->value('id') => ['paid' => 1, 'bonus' => 0]],
        ])->assertNotFound();
        $this->actingAs($intruder)->get(route('contracts.index'))->assertDontSee($foreign->number);
    }

    public function test_executor_cannot_enter_payments(): void
    {
        $contract = $this->activeContract();
        $line = $this->budgetLine();

        $this->actingAs($contract->creator)->post(route('contracts.payments.store', $contract), [
            'payment_stage_id' => $contract->stages()->value('id'),
            'budget_line_id' => $line->id,
            'amount' => '750',
            'paid_on' => '2026-03-18',
            'reference' => 'PP-1',
        ])->assertForbidden();
        $this->actingAs($contract->creator)->get(route('budget-lines.index'))->assertForbidden();
    }

    public function test_accountant_cannot_decide_penalty_or_change_contract(): void
    {
        $contract = $this->activeContract();
        $draft = $this->draftContract();
        $accountant = User::factory()->accountant()->create();

        $this->actingAs($accountant)->post(route('contracts.penalty.approve', $contract), ['as_of' => '2026-03-15'])->assertForbidden();
        $this->actingAs($accountant)->post(route('contracts.penalty.reject', $contract), ['as_of' => '2026-03-15', 'reason' => 'yo\'q'])->assertForbidden();
        $this->actingAs($accountant)->post(route('contracts.activate', $draft))->assertForbidden();
        $this->actingAs($accountant)->post(route('contracts.items.store', $draft), [
            'name' => 'X', 'paid_quantity' => 1, 'bonus_quantity' => 0, 'unit_price' => '1',
        ])->assertForbidden();

        $this->assertSame(0, PenaltyDecision::query()->count());
        $this->assertSame(1, $draft->items()->count());
    }

    public function test_head_sees_all_contracts_but_cannot_create_or_pay(): void
    {
        $contract = $this->activeContract();
        $head = User::factory()->head()->create();

        $this->actingAs($head)->get(route('contracts.show', $contract))->assertOk();
        $this->actingAs($head)->post(route('contracts.store'), [
            'number' => 'X-1', 'supplier' => 'S', 'start_date' => '2026-03-01', 'duration_days' => 5,
        ])->assertForbidden();
        $this->actingAs($head)->get(route('contracts.accounting', $contract))->assertForbidden();
        $this->assertSame(1, Contract::query()->count());
    }
}
