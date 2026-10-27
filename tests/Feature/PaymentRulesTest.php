<?php

namespace Tests\Feature;

use App\Models\BudgetLine;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use LogicException;
use Tests\Support\BuildsContracts;
use Tests\TestCase;

class PaymentRulesTest extends TestCase
{
    use BuildsContracts;
    use RefreshDatabase;

    private User $accountant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-03-20 10:00:00');
        $this->accountant = User::factory()->accountant()->create();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function pay(Contract $contract, BudgetLine $line, array $overrides = []): TestResponse
    {
        return $this->actingAs($this->accountant)->post(route('contracts.payments.store', $contract), [
            'payment_stage_id' => $contract->stages()->where('sequence', 1)->value('id'),
            'budget_line_id' => $line->id,
            'amount' => '750',
            'paid_on' => '2026-03-18',
            'reference' => 'PP-1',
            ...$overrides,
        ]);
    }

    public function test_checks_stop_at_first_failure_in_documented_order(): void
    {
        $line = $this->budgetLine(3, '100.00');

        $this->pay($this->draftContract(), $line, ['amount' => '0'])
            ->assertSessionHasErrors('general')
            ->assertSessionDoesntHaveErrors(['amount', 'budget_line_id']);
    }

    public function test_next_stage_requires_previous_stage_fully_paid(): void
    {
        $contract = $this->activeContract();
        $this->deliver($contract, '2026-03-12', 1000);
        $line = $this->budgetLine();

        $this->pay($contract, $line, ['amount' => '100']);

        $this->pay($contract, $line, [
            'payment_stage_id' => $contract->stages()->where('sequence', 2)->value('id'),
            'amount' => '100',
        ])->assertSessionHasErrors(['payment_stage_id' => "To'lov qabul qilinmadi: oldingi «Avans» bosqichi to'liq to'lanmagan (qoldiq: 650,00). Avval uni to'lang."]);
    }

    public function test_amount_must_be_positive(): void
    {
        $this->pay($this->activeContract(), $this->budgetLine(), ['amount' => '0'])
            ->assertSessionHasErrors(['amount' => "To'lov summasi noldan katta bo'lishi kerak."]);
    }

    public function test_payment_date_must_match_budget_fiscal_year(): void
    {
        $this->pay($this->activeContract(), $this->budgetLine(), ['paid_on' => '2025-12-30'])
            ->assertSessionHasErrors(['paid_on' => "To'lov qabul qilinmadi: to'lov sanasi (30.12.2025) smeta moliyaviy yiliga (2026) mos emas."]);
    }

    public function test_payment_cannot_exceed_budget_month_remaining(): void
    {
        $line = $this->budgetLine(3, '500.00');

        $this->pay($this->activeContract(), $line)
            ->assertSessionHasErrors(['budget_line_id' => "To'lov qabul qilinmadi: smeta qoldig'i yetarli emas — 2026 · Dori vositalari · Mart qoldig'i 500,00, to'lov summasi 750,00."]);
        $this->assertSame(0, Payment::query()->count());
    }

    public function test_payment_cannot_be_voided_twice_and_is_never_deleted(): void
    {
        $contract = $this->activeContract();
        $this->pay($contract, $this->budgetLine());
        $payment = Payment::query()->firstOrFail();
        $void = fn () => $this->actingAs($this->accountant)
            ->post(route('contracts.payments.void', [$contract, $payment]), ['void_reason' => [$payment->id => 'Xato summa']]);

        $void()->assertSessionHasNoErrors();
        $void()->assertSessionHasErrors('general');

        $this->expectException(LogicException::class);
        $payment->refresh()->delete();
    }

    public function test_database_rejects_non_positive_payment_even_when_application_is_bypassed(): void
    {
        $this->expectException(QueryException::class);

        Payment::factory()->create(['amount' => '0.00']);
    }

    public function test_void_requires_reason(): void
    {
        $contract = $this->activeContract();
        $this->pay($contract, $this->budgetLine());
        $payment = Payment::query()->firstOrFail();

        $this->actingAs($this->accountant)
            ->post(route('contracts.payments.void', [$contract, $payment]), ['void_reason' => [$payment->id => '']])
            ->assertSessionHasErrors("void_reason.{$payment->id}");
        $this->assertFalse($payment->refresh()->isVoided());
    }
}
