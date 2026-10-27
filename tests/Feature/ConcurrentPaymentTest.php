<?php

namespace Tests\Feature;

use App\Models\BudgetLine;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\User;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Tests\Support\BuildsContracts;
use Tests\TestCase;

class ConcurrentPaymentTest extends TestCase
{
    use BuildsContracts;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate:fresh', ['--force' => true]);
    }

    protected function tearDown(): void
    {
        $this->artisan('migrate:fresh', ['--force' => true]);
        DB::disconnect();

        parent::tearDown();
    }

    public function test_two_accountants_paying_the_same_stage_only_one_succeeds(): void
    {
        $year = CarbonImmutable::today()->year;
        $contract = $this->activeContract(year: (string) $year);
        $line = $this->budgetLine(CarbonImmutable::today()->month, '3000.00', $year);

        $results = $this->payConcurrently([
            [$contract, 1, $line, '500'],
            [$contract, 1, $line, '500'],
        ]);

        $this->assertSame(['ok', 'rejected'], $this->sortedStatuses($results));
        $this->assertStringContainsString("qolgan summasidan (250,00) ko'p", $this->rejectionMessage($results));
        $this->assertSame(1, Payment::query()->count());
    }

    public function test_two_accountants_using_last_budget_balance_only_one_succeeds(): void
    {
        $year = CarbonImmutable::today()->year;
        $line = $this->budgetLine(CarbonImmutable::today()->month, '1000.00', $year);
        $first = $this->activeContract(year: (string) $year);
        $second = $this->activeContract(year: (string) $year);

        $results = $this->payConcurrently([
            [$first, 1, $line, '750'],
            [$second, 1, $line, '750'],
        ]);

        $this->assertSame(['ok', 'rejected'], $this->sortedStatuses($results));
        $this->assertStringContainsString("smeta qoldig'i yetarli emas", $this->rejectionMessage($results));
        $this->assertSame('750.00', Money::of((string) Payment::query()->sum('amount'))->amount);
    }

    /**
     * @param  list<array{Contract, int, BudgetLine, string}>  $payments
     * @return list<array{status: string, message?: string}>
     */
    private function payConcurrently(array $payments): array
    {
        $accountant = User::factory()->accountant()->create();
        $startAt = (string) (microtime(true) + 3);
        $environment = [
            'DB_CONNECTION' => config('database.default'),
            'DB_DATABASE' => config('database.connections.'.config('database.default').'.database'),
            'DB_URL' => '',
        ];

        $processes = array_map(function (array $payment) use ($accountant, $startAt, $environment) {
            [$contract, $sequence, $line, $amount] = $payment;

            return Process::env($environment)->timeout(60)->start([
                PHP_BINARY,
                base_path('tests/Support/concurrent-payment.php'),
                (string) $contract->id,
                (string) $contract->stages()->where('sequence', $sequence)->value('id'),
                (string) $line->id,
                $amount,
                (string) $accountant->id,
                $startAt,
            ]);
        }, $payments);

        return array_map(function ($process): array {
            $result = $process->wait();
            $this->assertTrue($result->successful(), $result->errorOutput().$result->output());

            return json_decode($result->output(), true, flags: JSON_THROW_ON_ERROR);
        }, $processes);
    }

    /**
     * @param  list<array{status: string}>  $results
     * @return list<string>
     */
    private function sortedStatuses(array $results): array
    {
        $statuses = array_column($results, 'status');
        sort($statuses);

        return $statuses;
    }

    /**
     * @param  list<array{status: string, message?: string}>  $results
     */
    private function rejectionMessage(array $results): string
    {
        return collect($results)->firstWhere('status', 'rejected')['message'] ?? '';
    }
}
