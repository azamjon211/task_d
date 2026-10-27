<?php

use App\Exceptions\BusinessRuleException;
use App\Models\Contract;
use App\Models\User;
use App\Repositories\PaymentRepository;
use App\Services\Accounting\PaymentData;
use App\Services\Accounting\PaymentService;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Console\Kernel;
use Tests\Support\SlowPaymentRepository;

require __DIR__.'/../../vendor/autoload.php';

$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$app->bind(PaymentRepository::class, SlowPaymentRepository::class);

[, $contractId, $stageId, $budgetLineId, $amount, $accountantId, $startAt] = $argv;

while (microtime(true) < (float) $startAt) {
    usleep(1_000);
}

try {
    $app->make(PaymentService::class)->register(
        Contract::query()->findOrFail((int) $contractId),
        User::query()->findOrFail((int) $accountantId),
        new PaymentData((int) $stageId, (int) $budgetLineId, Money::of($amount), CarbonImmutable::today(), 'PP-'.getmypid()),
    );

    echo json_encode(['status' => 'ok']);
} catch (BusinessRuleException $exception) {
    echo json_encode(['status' => 'rejected', 'message' => $exception->getMessage()]);
}
