<?php

namespace Tests\Feature;

use App\Models\BudgetLine;
use App\Models\Contract;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagesRenderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-03-20 10:00:00');
        $this->seed(DatabaseSeeder::class);
    }

    public function test_demo_users_can_log_in(): void
    {
        $this->get(route('login'))->assertOk();

        $this->post('/login', ['email' => 'buxgalter@demo.uz', 'password' => 'password'])
            ->assertRedirect(route('contracts.index'));
        $this->assertAuthenticatedAs(User::query()->where('email', 'buxgalter@demo.uz')->first());
    }

    public function test_executor_pages_render(): void
    {
        $executor = User::query()->where('email', 'ijrochi@demo.uz')->firstOrFail();
        $draft = Contract::query()->where('number', 'like', '%/003')->firstOrFail();
        $active = Contract::query()->where('number', 'like', '%/002')->firstOrFail();

        $this->actingAs($executor);
        $this->get(route('contracts.index'))->assertOk()->assertSee($draft->number);
        $this->get(route('contracts.create'))->assertOk();
        $this->get(route('contracts.show', $draft))->assertOk()->assertSee('Faollashtirish');
        $this->get(route('contracts.edit', $draft))->assertOk();
        $this->get(route('contracts.show', [$active, 'as_of' => '2026-03-16']))->assertOk()->assertSee('35,00');
        $this->get(route('contracts.deliveries.create', $active))->assertOk();
    }

    public function test_head_and_accountant_pages_render(): void
    {
        $active = Contract::query()->where('number', 'like', '%/001')->firstOrFail();

        $this->actingAs(User::query()->where('email', 'boshliq@demo.uz')->firstOrFail());
        $this->get(route('contracts.show', [$active, 'as_of' => '2026-03-15']))->assertOk()->assertSee('37,50')->assertSee('Tasdiqlash');

        $this->actingAs(User::query()->where('email', 'buxgalter@demo.uz')->firstOrFail());
        $this->get(route('contracts.accounting', $active))->assertOk()->assertSee('750,00');
        $this->get(route('budget-lines.index'))->assertOk()->assertSee('3 000,00');
        $this->get(route('budget-lines.edit', BudgetLine::query()->first()))->assertOk();
    }
}
