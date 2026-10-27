<?php

namespace App\Services\Penalty;

use App\Support\Money;
use Carbon\CarbonImmutable;

final readonly class PenaltyDay
{
    public function __construct(
        public CarbonImmutable $date,
        public Money $undelivered,
        public Money $penalty,
    ) {}
}
