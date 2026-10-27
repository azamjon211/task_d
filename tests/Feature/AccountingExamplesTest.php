<?php

namespace Tests\Feature;

use App\Enums\StageStatus;
use App\Models\BudgetLine;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\User;
use App\Services\Accounting\AccountingService;
use App\Services\Accounting\BudgetService;
use App\Services\Accounting\StageSummary;
use App\Services\Contract\ContractService;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Support\BuildsContracts;
use Tests\TestCase;

class AccountingExamplesTest extends TestCase
{
    use BuildsContracts;
    use RefreshDatabase;

    private User $accountant;

    private BudgetLine $march;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-03-20 10:00:00');
        $this->accountant = User::factory()->accountant()->create();
        $this->march = $this->budgetLine(3, '3000.00');
        $this->budgetLine(4, '2000.00');
    }

    private function pay(Contract $contract, int $stageSequence, string $amount): TestResponse
    {
        return $this->actingAs($this->accountant)->post(route('contracts.payments.store', $contract), [
            'payment_stage_id' => $contract->stages()->where('sequence', $stageSequence)->value('id'),
            'budget_line_id' => $this->march->id,
            'amount' => $amount,
            'paid_on' => '2026-03-18',
            'reference' => 'PP-1',
        ]);
    }

    private function stage(Contract $contract, int $index): StageSummary
    {
        return app(AccountingService::class)->stageSummaries($contract->refresh())[$index];
    }

    private function marchRemaining(): string
    {
        return app(BudgetService::class)->lines()->firstOrFail(fn ($line) => $line->is($this->march))->remaining()->amount;
    }

    public function test_example_a_advance_is_rejected_for_draft_and_accepted_after_activation(): void
    {
        $contract = $this->draftContract();

        $this->pay($contract, 1, '750')->assertSessionHasErrors(['general' => "To'lov qabul qilinmadi: shartnoma faol emas (holati: Qoralama). To'lov faqat Faol yoki Yakunlangan shartnomaga kiritiladi."]);
        $this->assertSame(0, Payment::query()->count());

        app(ContractService::class)->activate($contract);

        $this->pay($contract, 1, '750')->assertSessionHasNoErrors();
        $this->assertSame('2250.00', $this->marchRemaining());
        $this->assertSame(StageStatus::Paid, $this->stage($contract, 0)->status());
    }

    public function test_example_b_delivery_stage_requires_full_delivery_and_respects_stage_balance(): void
    {
        $contract = $this->activeContract();
        $this->pay($contract, 1, '750')->assertSessionHasNoErrors();

        $this->pay($contract, 2, '1000')->assertSessionHasErrors(['payment_stage_id' => "To'lov qabul qilinmadi: «Yetkazib berish» bosqichining to'lash sharti bajarilmagan — pullik tovar hali to'liq kelmagan."]);

        $this->deliver($contract, '2026-03-12', 1000);

        $this->pay($contract, 2, '1000')->assertSessionHasNoErrors();
        $this->assertSame(StageStatus::PartiallyPaid, $this->stage($contract, 1)->status());
        $this->assertSame('750.00', $this->stage($contract, 1)->remaining()->amount);
        $this->assertSame('1250.00', $this->marchRemaining());

        $this->pay($contract, 2, '800')->assertSessionHasErrors(['amount' => "To'lov qabul qilinmadi: summa (800,00) «Yetkazib berish» bosqichining qolgan summasidan (750,00) ko'p."]);

        $this->pay($contract, 2, '750')->assertSessionHasNoErrors();
        $this->assertSame(StageStatus::Paid, $this->stage($contract, 1)->status());
    }

    public function test_example_c_voided_payment_is_kept_and_balances_are_recalculated(): void
    {
        $contract = $this->activeContract();
        $this->pay($contract, 1, '750');
        $this->deliver($contract, '2026-03-12', 1000);
        $this->pay($contract, 2, '1000');
        $wrong = Payment::query()->latest('id')->first();

        $this->actingAs($this->accountant)
            ->post(route('contracts.payments.void', [$contract, $wrong]), ['void_reason' => [$wrong->id => '900 o\'rniga 1000 kiritilgan']])
            ->assertSessionHasNoErrors();

        $wrong->refresh();
        $this->assertTrue($wrong->isVoided());
        $this->assertSame('1000.00', $wrong->amount->amount);
        $this->assertSame($this->accountant->id, $wrong->voided_by);
        $this->assertSame('1750.00', $this->stage($contract, 1)->remaining()->amount);
        $this->assertSame('2250.00', $this->marchRemaining());

        $this->pay($contract, 2, '900')->assertSessionHasNoErrors();
        $this->assertSame('850.00', $this->stage($contract, 1)->remaining()->amount);
        $this->assertSame('1350.00', $this->marchRemaining());
        $this->assertSame(3, Payment::query()->count(), 'Bekor qilingan yozuv reestrda qoladi');

        $this->get(route('contracts.accounting', $contract))
            ->assertOk()
            ->assertViewHas('overview', fn ($overview) => $overview->voidedPayments->pluck('id')->all() === [$wrong->id] && $overview->activePayments->count() === 2);
    }

    public function test_example_d_accounting_shows_only_approved_penalty_amount(): void
    {
        $contract = $this->activeContract();
        $head = User::factory()->head()->create();
        $accountingPenalty = fn (): string => $this->actingAs($this->accountant)
            ->get(route('contracts.accounting', $contract))
            ->assertOk()
            ->viewData('overview')->approvedPenalty
            ->amount;

        $this->actingAs($head)->get(route('contracts.show', [$contract, 'as_of' => '2026-03-15']))
            ->assertOk()
            ->assertViewHas('penalty', fn ($penalty) => $penalty->total->amount === '37.50');
        $this->assertSame('0.00', $accountingPenalty());

        $this->actingAs($head)->post(route('contracts.penalty.approve', $contract), ['as_of' => '2026-03-15'])->assertSessionHasNoErrors();
        $this->assertSame('37.50', $accountingPenalty());

        $this->actingAs($head)->post(route('contracts.penalty.approve', $contract), ['as_of' => '2026-03-15', 'approved_amount' => '30,00'])->assertSessionHasNoErrors();
        $this->assertSame('30.00', $accountingPenalty());

        $this->actingAs($head)->post(route('contracts.penalty.reject', $contract), ['as_of' => '2026-03-15', 'reason' => 'Fors-major'])->assertSessionHasNoErrors();
        $this->assertSame('0.00', $accountingPenalty());
    }

    public function test_penalty_approved_after_final_payment_is_shown_as_overpaid_and_to_recover(): void
    {
        $contract = $this->activeContract();
        $this->deliver($contract, '2026-03-15', 1000);
        $this->pay($contract, 1, '750')->assertSessionHasNoErrors();
        $this->pay($contract, 2, '1750')->assertSessionHasNoErrors();

        $this->actingAs(User::factory()->head()->create())
            ->post(route('contracts.penalty.approve', $contract), ['as_of' => '2026-03-20'])
            ->assertSessionHasNoErrors();

        $overview = $this->actingAs($this->accountant)->get(route('contracts.accounting', $contract))->assertOk()->viewData('overview');
        $finalStage = $overview->summaries[1];

        $this->assertSame('37.50', $overview->approvedPenalty->amount);
        $this->assertSame(StageStatus::Paid, $finalStage->status());
        $this->assertSame('0.00', $finalStage->remaining()->amount);
        $this->assertSame('37.50', $finalStage->overpaid()->amount);
        $this->assertSame('37.50', $overview->penaltyToRecover->amount);
        $this->assertSame(2, Payment::query()->whereNull('voided_at')->count(), "Reestr o'zgartirilmaydi");
    }

    public function test_approved_penalty_reduces_final_stage_payment(): void
    {
        $contract = $this->activeContract();
        $head = User::factory()->head()->create();
        $this->actingAs($head)->post(route('contracts.penalty.approve', $contract), ['as_of' => '2026-03-15']);
        $this->pay($contract, 1, '750');
        $this->deliver($contract, '2026-03-16', 1000);

        $this->assertSame('1712.50', $this->stage($contract, 1)->remaining()->amount);
        $this->pay($contract, 2, '1750')->assertSessionHasErrors('amount');
        $this->pay($contract, 2, '1712.50')->assertSessionHasNoErrors();
        $this->assertSame(StageStatus::Paid, $this->stage($contract, 1)->status());
        $this->assertSame('0.00', $this->stage($contract, 1)->remaining()->amount);
        $this->assertTrue(Money::of('2462.50')->equals(Money::of((string) Payment::query()->sum('amount'))));
    }
}
