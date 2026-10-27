<?php

namespace App\Services\Accounting;

use App\Support\Money;
use Carbon\CarbonImmutable;

final readonly class PaymentData
{
    public function __construct(
        public int $stageId,
        public int $budgetLineId,
        public Money $amount,
        public CarbonImmutable $paidOn,
        public string $reference,
    ) {}
}
