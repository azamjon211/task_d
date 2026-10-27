<?php

namespace App\Services\Accounting;

use App\Exceptions\BusinessRuleException;
use App\Models\BudgetLine;
use App\Repositories\BudgetLineRepository;
use App\Repositories\PaymentRepository;
use App\Support\Money;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class BudgetService
{
    public function __construct(
        private readonly BudgetLineRepository $budgetLines,
        private readonly PaymentRepository $payments,
    ) {}

    /**
     * @return Collection<int, BudgetLine>
     */
    public function lines(): Collection
    {
        return $this->budgetLines->allWithSpent();
    }

    /**
     * @param  array{fiscal_year: int, expense_item: string, month: int, allocated_amount: Money}  $attributes
     */
    public function create(array $attributes): BudgetLine
    {
        try {
            return $this->budgetLines->create($attributes);
        } catch (UniqueConstraintViolationException) {
            throw new BusinessRuleException('Bu yil, xarajat moddasi va oy uchun smeta qatori allaqachon mavjud.', 'month');
        }
    }

    public function updateAllocated(BudgetLine $budgetLine, Money $allocated): BudgetLine
    {
        return DB::transaction(function () use ($budgetLine, $allocated): BudgetLine {
            $budgetLine = $this->budgetLines->lockForUpdate($budgetLine->getKey());
            $spent = $this->payments->activeSpentForBudgetLine($budgetLine);

            if ($allocated->lessThan($spent)) {
                throw new BusinessRuleException(
                    "Ajratilgan summa ({$allocated->format()}) shu oy bo'yicha to'langan summadan ({$spent->format()}) kam bo'lishi mumkin emas.",
                    'allocated_amount',
                );
            }

            return $this->budgetLines->updateAllocated($budgetLine, $allocated);
        });
    }

    public function delete(BudgetLine $budgetLine): void
    {
        DB::transaction(function () use ($budgetLine): void {
            $budgetLine = $this->budgetLines->lockForUpdate($budgetLine->getKey());

            if ($this->payments->existsForBudgetLine($budgetLine)) {
                throw new BusinessRuleException("Bu smeta qatoridan to'lovlar qilingan (reestrda bor), uni o'chirib bo'lmaydi.");
            }

            $this->budgetLines->delete($budgetLine);
        });
    }
}
