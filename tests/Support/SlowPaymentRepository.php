<?php

namespace Tests\Support;

use App\Models\BudgetLine;
use App\Repositories\PaymentRepository;
use App\Support\Money;

class SlowPaymentRepository extends PaymentRepository
{
    public function activeSpentForBudgetLine(BudgetLine $budgetLine): Money
    {
        $spent = parent::activeSpentForBudgetLine($budgetLine);

        usleep(700_000);

        return $spent;
    }
}
